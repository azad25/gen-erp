<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Domain action for user authentication.
 */
class AuthenticateUserAction
{
    /**
     * Attempt to authenticate user with credentials.
     */
    public function execute(string $email, string $password): ?User
    {
        if (Auth::attempt(['email' => $email, 'password' => $password])) {
            return Auth::user();
        }

        return null;
    }

    /**
     * Check if user account is locked.
     */
    public function isAccountLocked(User $user): bool
    {
        return $user->locked_until && $user->locked_until->isFuture();
    }

    /**
     * Get remaining lock time in minutes.
     */
    public function getRemainingLockTime(User $user): int
    {
        if (! $this->isAccountLocked($user)) {
            return 0;
        }

        return (int) now()->diffInMinutes($user->locked_until, false);
    }

    /**
     * Reset failed login attempts.
     */
    public function resetFailedAttempts(User $user): void
    {
        if ($user->failed_login_count > 0) {
            $user->update([
                'failed_login_count' => 0,
                'locked_until' => null,
            ]);
        }
    }

    /**
     * Check if user requires email verification.
     */
    public function requiresEmailVerification(User $user): bool
    {
        // Bypass for dev admin
        if ($user->email === 'dev@generp.test') {
            return false;
        }

        return ! $user->hasVerifiedEmail();
    }

    /**
     * Check if user has two-factor authentication enabled.
     */
    public function hasTwoFactorEnabled(User $user): bool
    {
        return ! is_null($user->two_factor_confirmed_at);
    }
}
