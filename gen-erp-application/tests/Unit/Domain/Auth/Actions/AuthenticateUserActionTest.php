<?php

namespace Tests\Unit\Domain\Auth\Actions;

use App\Domain\Auth\Actions\AuthenticateUserAction;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticateUserActionTest extends TestCase
{
    use RefreshDatabase;

    private AuthenticateUserAction $action;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new AuthenticateUserAction;
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
    }

    /** @test */
    public function it_can_authenticate_user_with_valid_credentials(): void
    {
        // Act
        $authenticatedUser = $this->action->execute('test@example.com', 'password123');

        // Assert
        $this->assertInstanceOf(User::class, $authenticatedUser);
        $this->assertEquals($this->user->id, $authenticatedUser->id);
    }

    /** @test */
    public function it_returns_null_with_invalid_credentials(): void
    {
        // Act
        $authenticatedUser = $this->action->execute('test@example.com', 'wrong-password');

        // Assert
        $this->assertNull($authenticatedUser);
    }

    /** @test */
    public function it_returns_null_with_non_existent_email(): void
    {
        // Act
        $authenticatedUser = $this->action->execute('nonexistent@example.com', 'password123');

        // Assert
        $this->assertNull($authenticatedUser);
    }

    /** @test */
    public function it_can_check_if_account_is_locked(): void
    {
        // Arrange
        $lockedUser = User::factory()->create([
            'locked_until' => now()->addMinutes(30),
        ]);
        $unlockedUser = User::factory()->create([
            'locked_until' => null,
        ]);

        // Act & Assert
        $this->assertTrue($this->action->isAccountLocked($lockedUser));
        $this->assertFalse($this->action->isAccountLocked($unlockedUser));
    }

    /** @test */
    public function it_returns_false_for_expired_lock(): void
    {
        // Arrange
        $user = User::factory()->create([
            'locked_until' => now()->subMinutes(10), // Expired lock
        ]);

        // Act & Assert
        $this->assertFalse($this->action->isAccountLocked($user));
    }

    /** @test */
    public function it_can_get_remaining_lock_time(): void
    {
        // Arrange
        $lockedUser = User::factory()->create([
            'locked_until' => now()->addMinutes(15)->addSeconds(30), // Add buffer for timing
        ]);
        $unlockedUser = User::factory()->create([
            'locked_until' => null,
        ]);

        // Act & Assert
        $remainingTime = $this->action->getRemainingLockTime($lockedUser);
        $this->assertGreaterThanOrEqual(14, $remainingTime); // Allow for timing variance
        $this->assertLessThanOrEqual(16, $remainingTime);
        $this->assertEquals(0, $this->action->getRemainingLockTime($unlockedUser));
    }

    /** @test */
    public function it_can_reset_failed_attempts(): void
    {
        // Arrange
        $user = User::factory()->create([
            'failed_login_count' => 5,
            'locked_until' => now()->addMinutes(30),
        ]);

        // Act
        $this->action->resetFailedAttempts($user);

        // Assert
        $user->refresh();
        $this->assertEquals(0, $user->failed_login_count);
        $this->assertNull($user->locked_until);
    }

    /** @test */
    public function it_does_not_reset_when_no_failed_attempts(): void
    {
        // Arrange
        $user = User::factory()->create([
            'failed_login_count' => 0,
            'locked_until' => null,
        ]);

        // Act
        $this->action->resetFailedAttempts($user);

        // Assert
        $user->refresh();
        $this->assertEquals(0, $user->failed_login_count);
        $this->assertNull($user->locked_until);
    }

    /** @test */
    public function it_checks_email_verification_requirement(): void
    {
        // Arrange
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $devAdmin = User::factory()->create([
            'email' => 'dev@generp.test',
            'email_verified_at' => null,
        ]);

        // Act & Assert
        $this->assertFalse($this->action->requiresEmailVerification($verifiedUser));
        $this->assertTrue($this->action->requiresEmailVerification($unverifiedUser));
        $this->assertFalse($this->action->requiresEmailVerification($devAdmin)); // Dev admin bypass
    }

    /** @test */
    public function it_checks_two_factor_authentication_status(): void
    {
        // Arrange
        $userWith2FA = User::factory()->create([
            'two_factor_confirmed_at' => now(),
        ]);
        $userWithout2FA = User::factory()->create([
            'two_factor_confirmed_at' => null,
        ]);

        // Act & Assert
        $this->assertTrue($this->action->hasTwoFactorEnabled($userWith2FA));
        $this->assertFalse($this->action->hasTwoFactorEnabled($userWithout2FA));
    }
}
