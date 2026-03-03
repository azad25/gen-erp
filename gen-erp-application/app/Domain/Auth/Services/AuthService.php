<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Actions\AuthenticateUserAction;
use App\Domain\Auth\Actions\RegisterUserAction;
use App\Domain\Auth\Actions\SetupCompanyAction;
use App\Domain\Auth\DataTransferObjects\CompanySetupData;
use App\Domain\Auth\DataTransferObjects\UserRegistrationData;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\FailedLoginAttempt;
use App\Domain\Auth\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;

/**
 * Authentication Service
 *
 * Handles all authentication-related business logic including:
 * - User login/logout
 * - User registration
 * - Two-factor authentication
 * - Token management
 * - Company switching
 * - Failed login tracking
 */
class AuthService
{
    public function __construct(
        private AuthenticateUserAction $authenticateUserAction,
        private RegisterUserAction $registerUserAction,
        private SetupCompanyAction $setupCompanyAction,
    ) {}

    /**
     * Attempt to authenticate a user with email and password
     */
    public function login(string $email, string $password, string $ipAddress, string $userAgent): array
    {
        // Check rate limiting
        $rateLimitKey = 'login:'.$ipAddress;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return [
                'success' => false,
                'message' => __('Too many login attempts. Please try again in :seconds seconds.', [
                    'seconds' => $seconds,
                ]),
                'retry_after' => $seconds,
            ];
        }

        $user = User::where('email', $email)->first();

        // Check if account is locked
        if ($user && $this->authenticateUserAction->isAccountLocked($user)) {
            $minutesRemaining = $this->authenticateUserAction->getRemainingLockTime($user);

            return [
                'success' => false,
                'message' => __('Your account is locked. Please try again in :minutes minutes.', [
                    'minutes' => max(1, $minutesRemaining),
                ]),
            ];
        }

        // Attempt authentication
        $authenticatedUser = $this->authenticateUserAction->execute($email, $password);

        if (! $authenticatedUser) {
            RateLimiter::hit($rateLimitKey, 60);
            $this->recordFailedAttempt($ipAddress, $userAgent, $email, $user);

            return [
                'success' => false,
                'message' => __('These credentials do not match our records.'),
            ];
        }

        RateLimiter::clear($rateLimitKey);

        // Reset failed login count on successful login
        $this->authenticateUserAction->resetFailedAttempts($authenticatedUser);

        // Email verification check
        if ($this->authenticateUserAction->requiresEmailVerification($authenticatedUser)) {
            return [
                'success' => false,
                'message' => __('Please verify your email address first.'),
                'requires_verification' => true,
            ];
        }

        // 2FA check
        if ($this->authenticateUserAction->hasTwoFactorEnabled($authenticatedUser)) {
            return $this->handleTwoFactorRequired($authenticatedUser);
        }

        return $this->completeLogin($authenticatedUser);
    }

    /**
     * Register a new user (without company)
     */
    public function register(UserRegistrationData $userData): array
    {
        $user = $this->registerUserAction->execute($userData);

        // Create token without company context (user needs to setup company first)
        $token = $user->createToken('auth-token', ['*']);

        return [
            'success' => true,
            'user' => $user,
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => null,
            'requires_company_setup' => true,
            'message' => __('Registration successful. Please setup your company.'),
        ];
    }

    /**
     * Setup company for a user (first-time setup or adding new company)
     */
    public function setupCompany(User $user, CompanySetupData $companyData): array
    {
        $company = $this->setupCompanyAction->execute($user, $companyData);

        // Update current token's company_id
        $currentToken = $user->currentAccessToken();
        if ($currentToken) {
            $currentToken->update(['company_id' => $company->id]);
        }

        return [
            'success' => true,
            'company' => $company,
            'permissions' => $user->getPermissionsForCompany($company->id),
            'message' => __('Company setup completed successfully.'),
        ];
    }

    /**
     * Handle two-factor authentication requirement
     */
    private function handleTwoFactorRequired(User $user): array
    {
        // Create temporary token for 2FA challenge (expires in 10 minutes)
        $tempToken = $user->createToken('2fa-challenge', ['2fa:challenge'], now()->addMinutes(10));

        return [
            'success' => true,
            'two_factor_required' => true,
            'temp_token' => $tempToken->plainTextToken,
            'message' => __('Please enter your two-factor authentication code.'),
        ];
    }

    /**
     * Complete the login process and return user data with token
     */
    private function completeLogin(User $user): array
    {
        // Get user's companies
        $companies = $user->companies;

        // Determine active company
        $activeCompany = $this->determineActiveCompany($user, $companies);

        // Create token with company context
        $token = $user->createToken('auth-token', ['*']);

        // Store company_id in token if available
        if ($activeCompany) {
            $token->accessToken->update(['company_id' => $activeCompany->id]);
        }

        return [
            'success' => true,
            'user' => $user,
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => null,
            'active_company' => $activeCompany,
            'requires_company_selection' => $companies->count() > 1 && ! $activeCompany,
            'requires_company_setup' => $companies->count() === 0,
            'companies' => $companies,
            'message' => __('Login successful.'),
        ];
    }

    /**
     * Verify two-factor authentication code
     */
    public function verifyTwoFactorCode(User $user, string $code): array
    {
        // Check if current token has 2FA challenge ability
        $currentToken = $user->currentAccessToken();
        if (! $currentToken->can('2fa:challenge')) {
            return [
                'success' => false,
                'message' => __('Invalid token for 2FA challenge.'),
            ];
        }

        // Verify 2FA code using Google2FA
        $google2fa = new Google2FA;
        $valid = $google2fa->verifyKey(
            decrypt($user->two_factor_secret),
            $code,
            2 // Allow 2 clock skew
        );

        if (! $valid) {
            return [
                'success' => false,
                'message' => __('Invalid two-factor code.'),
            ];
        }

        // Revoke the temporary 2FA token
        $currentToken->delete();

        return $this->completeLogin($user);
    }

    /**
     * Logout user by revoking current token
     */
    public function logout(User $user): array
    {
        // Revoke the current token
        $user->currentAccessToken()->delete();

        return [
            'success' => true,
            'message' => __('Logged out successfully.'),
        ];
    }

    /**
     * Get user data with company context
     */
    public function getUserData(User $user): array
    {
        // Get company from token
        $token = $user->currentAccessToken();
        $companyId = $token->company_id ?? $user->last_active_company_id;
        $company = $companyId ? $user->companies()->find($companyId) : null;

        return [
            'success' => true,
            'user' => $user,
            'company' => $company,
            'permissions' => $company ? $user->getPermissionsForCompany($company->id) : [],
            'subscription' => $company?->activeSubscription?->plan?->slug,
            'companies' => $user->companies,
            'requires_company_setup' => $user->companies()->count() === 0,
        ];
    }

    /**
     * Switch user's active company
     */
    public function switchCompany(User $user, int $companyId): array
    {
        // Check if user has access to this company
        $company = $user->companies()->where('companies.id', $companyId)->first();

        if (! $company) {
            return [
                'success' => false,
                'message' => __('Company not found or access denied.'),
            ];
        }

        if (! $company->is_active) {
            return [
                'success' => false,
                'message' => __('Company is not active.'),
            ];
        }

        // Update user's last active company
        $user->update(['last_active_company_id' => $companyId]);

        // Update current token's company_id
        $currentToken = $user->currentAccessToken();
        $currentToken->update(['company_id' => $companyId]);

        return [
            'success' => true,
            'company' => $company,
            'permissions' => $user->getPermissionsForCompany($companyId),
            'message' => __('Company switched successfully.'),
        ];
    }

    /**
     * Determine the active company for a user
     */
    private function determineActiveCompany(User $user, $companies): ?Company
    {
        if ($companies->count() === 1) {
            $activeCompany = $companies->first();
            $user->update(['last_active_company_id' => $activeCompany->id]);

            return $activeCompany;
        }

        if ($user->last_active_company_id) {
            return $companies->find($user->last_active_company_id);
        }

        return null;
    }

    /**
     * Record a failed login attempt
     */
    private function recordFailedAttempt(string $ipAddress, string $userAgent, string $email, ?User $user): void
    {
        // Write to failed_login_attempts table
        FailedLoginAttempt::create([
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
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

            // Send lockout notification email via queued job
            if (class_exists(\App\Jobs\SendLockoutNotificationJob::class)) {
                dispatch(new \App\Jobs\SendLockoutNotificationJob($user));
            }
        }
    }
}
