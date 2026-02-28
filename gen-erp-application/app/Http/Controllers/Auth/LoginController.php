<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\FailedLoginAttempt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Handles login with account lockout and failed attempt tracking.
 */
class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];

        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        // Check if account is locked
        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            $minutesRemaining = (int) now()->diffInMinutes($user->locked_until, false);

            return back()
                ->withInput(['email' => $email])
                ->withErrors([
                    'email' => __('Your account is locked. Please try again in :minutes minutes.', [
                        'minutes' => max(1, $minutesRemaining),
                    ]),
                ]);
        }

        // Attempt authentication
        $remember = $validated['remember'] ?? false;

        if (! Auth::attempt(['email' => $email, 'password' => $validated['password']], $remember)) {
            $this->recordFailedAttempt($request, $email, $user);

            return back()
                ->withInput(['email' => $email])
                ->withErrors(['email' => __('These credentials do not match our records.')]);
        }

        // Success: reset failed login count and regenerate session
        if ($user && $user->failed_login_count > 0) {
            $user->update([
                'failed_login_count' => 0,
                'locked_until' => null,
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function logout(\Illuminate\Http\Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Record a failed login attempt and handle lockout logic.
     */
    private function recordFailedAttempt(LoginRequest $request, string $email, ?User $user): void
    {
        // Write to failed_login_attempts table
        FailedLoginAttempt::create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'target_email' => $email,
        ]);

        if (! $user) {
            return;
        }

        $user->increment('failed_login_count');

        // Lock account after 10 failures
        if ($user->failed_login_count >= 10) {
            $user->update([
                'locked_until' => now()->addMinutes(30),
            ]);

            // TODO: Phase 2 — Send lockout notification email via queued job
            // Mail::to($user)->queue(new AccountLockedMail($user));
        }
    }
}
