<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\UserResource;
use App\Models\FailedLoginAttempt;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

/**
 * API Authentication Controller for SPA (cookie-based Sanctum auth)
 */
class AuthController extends Controller
{
    /**
     * Handle SPA login request
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $email = $request->validated('email');
        
        // Rate limiting — 5 attempts per IP per minute
        $key = 'login:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'success' => false,
                'message' => __('Too many login attempts. Please try again in :seconds seconds.', [
                    'seconds' => $seconds
                ]),
                'retry_after' => $seconds,
            ], 429);
        }

        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        // Check if account is locked
        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            $minutesRemaining = (int) now()->diffInMinutes($user->locked_until, false);

            return response()->json([
                'success' => false,
                'message' => __('Your account is locked. Please try again in :minutes minutes.', [
                    'minutes' => max(1, $minutesRemaining),
                ]),
            ], 403);
        }

        // Attempt authentication
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            // Record failed attempt
            $this->recordFailedAttempt($request, $email, $user);

            return response()->json([
                'success' => false,
                'message' => __('These credentials do not match our records.'),
            ], 401);
        }

        RateLimiter::clear($key);

        $user = Auth::user();

        // Reset failed login count on successful login
        if ($user->failed_login_count > 0) {
            $user->update([
                'failed_login_count' => 0,
                'locked_until' => null,
            ]);
        }

        // Email verification check (bypass for dev admin)
        $isDevAdmin = $user->email === 'dev@generp.test';
        
        if (!$user->hasVerifiedEmail() && !$isDevAdmin) {
            Auth::logout();
            
            return response()->json([
                'success' => false,
                'message' => __('Please verify your email address first.'),
                'requires_verification' => true,
            ], 403);
        }

        // 2FA check — if enabled, don't complete login yet
        if ($user->two_factor_confirmed_at) {
            // Store partial auth state in session
            session(['two_factor_user_id' => $user->id]);
            Auth::logout(); // log out — they haven't passed 2FA yet

            return response()->json([
                'success' => true,
                'two_factor_required' => true,
                'message' => __('Please enter your two-factor authentication code.'),
            ], 200);
        }

        // Regenerate session ID to prevent session fixation attacks
        $request->session()->regenerate();

        // Get user's companies
        $companies = $user->companies;

        // If user only has one company, set it as active automatically
        if ($companies->count() === 1) {
            session(['active_company_id' => $companies->first()->id]);
            $user->update(['last_active_company_id' => $companies->first()->id]);
        } elseif ($user->last_active_company_id) {
            // Restore last active company
            session(['active_company_id' => $user->last_active_company_id]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'requires_company_selection' => $companies->count() > 1 && !$user->last_active_company_id,
            ],
            'message' => __('Login successful.'),
        ]);
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken(); // new CSRF token

        return response()->json([
            'success' => true,
            'message' => __('Logged out successfully.'),
        ]);
    }

    /**
     * Get authenticated user info
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('Unauthenticated.'),
            ], 401);
        }

        $companyId = session('active_company_id');
        $company = $companyId ? $user->companies()->find($companyId) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'company' => $company ? new CompanyResource($company) : null,
                'permissions' => $company ? $user->getPermissionsForCompany($company->id) : [],
                'subscription' => $company?->activeSubscription?->plan?->slug,
            ],
        ]);
    }

    /**
     * Record a failed login attempt
     */
    private function recordFailedAttempt(Request $request, string $email, ?User $user): void
    {
        // Write to failed_login_attempts table
        FailedLoginAttempt::create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'target_email' => $email,
        ]);

        if (!$user) {
            return;
        }

        $user->increment('failed_login_count');

        // Lock account after 10 failures
        if ($user->failed_login_count >= 10) {
            $user->update([
                'locked_until' => now()->addMinutes(30),
            ]);

            // Send lockout notification email via queued job
            if (class_exists(\App\Jobs\SendLockoutNotificationJob::class)) {
                dispatch(new \App\Jobs\SendLockoutNotificationJob($user));
            }
        }
    }
}
