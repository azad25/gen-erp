<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Two-Factor Authentication Controller
 */
class TwoFactorController extends Controller
{
    /**
     * Handle 2FA challenge during login
     */
    public function challenge(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required_without:recovery_code|string|size:6',
            'recovery_code' => 'required_without:code|string',
        ]);

        $userId = session('two_factor_user_id');

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => __('No pending 2FA challenge.'),
            ], 400);
        }

        $user = User::findOrFail($userId);

        // Try TOTP code first
        if ($request->filled('code')) {
            $google2fa = new Google2FA();
            $valid = $google2fa->verifyKey(
                decrypt($user->two_factor_secret),
                $request->code
            );

            if (!$valid) {
                return response()->json([
                    'success' => false,
                    'message' => __('Invalid authentication code.'),
                ], 422);
            }
        }
        // Try recovery code
        elseif ($request->filled('recovery_code')) {
            $valid = $this->validateRecoveryCode($user, $request->recovery_code);
            
            if (!$valid) {
                return response()->json([
                    'success' => false,
                    'message' => __('Invalid recovery code.'),
                ], 422);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => __('Code or recovery code required.'),
            ], 422);
        }

        // 2FA passed — complete the login
        session()->forget('two_factor_user_id');
        Auth::loginUsingId($userId);
        $request->session()->regenerate();

        // Set active company if user has one
        $companies = $user->companies;
        if ($companies->count() === 1) {
            session(['active_company_id' => $companies->first()->id]);
        } elseif ($user->last_active_company_id) {
            session(['active_company_id' => $user->last_active_company_id]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
            ],
            'message' => __('Two-factor authentication successful.'),
        ]);
    }

    /**
     * Enable 2FA for the authenticated user
     */
    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user->two_factor_confirmed_at) {
            return response()->json([
                'success' => false,
                'message' => __('Two-factor authentication is already enabled.'),
            ], 400);
        }

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $user->update(['two_factor_secret' => encrypt($secret)]);

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return response()->json([
            'success' => true,
            'data' => [
                'secret' => $secret,
                'qr_code_url' => $qrCodeUrl,
            ],
            'message' => __('Scan the QR code with your authenticator app.'),
        ]);
    }

    /**
     * Confirm 2FA setup with a valid code
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!$user->two_factor_secret) {
            return response()->json([
                'success' => false,
                'message' => __('Two-factor authentication has not been enabled yet.'),
            ], 400);
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey(
            decrypt($user->two_factor_secret),
            $request->code
        );

        if (!$valid) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid authentication code. Please try again.'),
            ], 422);
        }

        // Generate recovery codes — shown once, stored hashed
        $recoveryCodes = collect(range(1, 10))
            ->map(fn() => strtoupper(Str::random(10)))
            ->toArray();

        $user->update([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(
                array_map(fn($code) => Hash::make($code), $recoveryCodes)
            )),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'recovery_codes' => $recoveryCodes, // shown to user ONCE
            ],
            'message' => __('Two-factor authentication has been enabled. Save your recovery codes in a safe place.'),
        ]);
    }

    /**
     * Disable 2FA for the authenticated user
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Verify password before disabling 2FA
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid password.'),
            ], 422);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Two-factor authentication has been disabled.'),
        ]);
    }

    /**
     * Validate a recovery code
     */
    private function validateRecoveryCode(User $user, string $code): bool
    {
        if (!$user->two_factor_recovery_codes) {
            return false;
        }

        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        foreach ($codes as $index => $hashedCode) {
            if (Hash::check($code, $hashedCode)) {
                // Invalidate used recovery code — each code is single-use
                unset($codes[$index]);
                
                $user->update([
                    'two_factor_recovery_codes' => encrypt(json_encode(array_values($codes)))
                ]);
                
                return true;
            }
        }

        return false;
    }
}
