<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

test('account locked after 10 failed login attempts', function (): void {
    // Clear rate limiter so throttle middleware doesn't interfere
    RateLimiter::clear('');

    $user = User::factory()->create([
        'email' => 'locked@example.com',
        'password' => 'correct-password',
    ]);

    // Attempt 10 failed logins — bypass throttle by resetting between batches
    for ($i = 0; $i < 10; $i++) {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class)
            ->post('/login', [
                'email' => 'locked@example.com',
                'password' => 'wrong-password',
            ]);
    }

    $user->refresh();
    expect($user->failed_login_count)->toBeGreaterThanOrEqual(10);
    expect($user->locked_until)->not->toBeNull();
    expect($user->locked_until->isFuture())->toBeTrue();
});

test('locked account rejects correct password', function (): void {
    $user = User::factory()->create([
        'email' => 'locked2@example.com',
        'password' => 'correct-password',
        'locked_until' => now()->addMinutes(30),
        'failed_login_count' => 10,
    ]);

    $response = $this->post('/login', [
        'email' => 'locked2@example.com',
        'password' => 'correct-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('failed login increments failed_login_count', function (): void {
    $user = User::factory()->create([
        'email' => 'counter@example.com',
        'password' => 'correct-password',
        'failed_login_count' => 0,
    ]);

    $this->post('/login', [
        'email' => 'counter@example.com',
        'password' => 'wrong-password',
    ]);

    $user->refresh();
    expect($user->failed_login_count)->toBe(1);
});

// ── Registration → Login Roundtrip ──────────────────────────────

test('registration saves user to database and logs them in', function (): void {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'newuser@example.com',
        'password' => 'MySecurePass123',
        'password_confirmation' => 'MySecurePass123',
        'phone' => '01712345678',
    ]);

    $response->assertRedirect(route('setup.company'));
    $this->assertAuthenticated();

    // User persisted in DB
    $user = User::where('email', 'newuser@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Test User');
    expect($user->phone)->toBe('01712345678');
});

test('login works with credentials from registration', function (): void {
    // Register
    $this->post('/register', [
        'name' => 'Roundtrip User',
        'email' => 'roundtrip@example.com',
        'password' => 'SecurePass456',
        'password_confirmation' => 'SecurePass456',
    ]);

    // Logout
    $this->post('/logout');
    $this->assertGuest();

    // Login with same credentials
    $response = $this->post('/login', [
        'email' => 'roundtrip@example.com',
        'password' => 'SecurePass456',
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticated();
});

test('login fails with wrong password', function (): void {
    $user = User::factory()->create([
        'email' => 'wrongpass@example.com',
        'password' => 'correct-password',
    ]);

    $response = $this->post('/login', [
        'email' => 'wrongpass@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

