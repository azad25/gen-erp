<?php

namespace Tests\Unit\Domain\Auth\Actions;

use App\Domain\Auth\Actions\RegisterUserAction;
use App\Domain\Auth\DataTransferObjects\UserRegistrationData;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterUserActionTest extends TestCase
{
    use RefreshDatabase;

    private RegisterUserAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new RegisterUserAction;
    }

    /** @test */
    public function it_can_register_a_new_user(): void
    {
        // Arrange
        $userData = new UserRegistrationData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123',
            phone: '01712345678'
        );

        // Act
        $user = $this->action->execute($userData);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('01712345678', $user->phone);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '01712345678',
        ]);
    }

    /** @test */
    public function it_can_register_user_without_phone(): void
    {
        // Arrange
        $userData = new UserRegistrationData(
            name: 'Jane Doe',
            email: 'jane@example.com',
            password: 'password123'
        );

        // Act
        $user = $this->action->execute($userData);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertEquals('jane@example.com', $user->email);
        $this->assertNull($user->phone);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /** @test */
    public function it_hashes_password_correctly(): void
    {
        // Arrange
        $userData = new UserRegistrationData(
            name: 'Test User',
            email: 'test@example.com',
            password: 'plaintext-password'
        );

        // Act
        $user = $this->action->execute($userData);

        // Assert
        $this->assertNotEquals('plaintext-password', $user->password);
        $this->assertTrue(Hash::check('plaintext-password', $user->password));
    }
}
