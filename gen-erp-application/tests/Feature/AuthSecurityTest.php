<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

test('account locked after 10 failed login attempts', function (): void {
    // Clear rate limiter so throttle middleware doesn't interfere
    RateLimiter::clear('');

    $user = User::factory()->create([
        'email' => 'locked@example.com',
        'password' => bcrypt('correct-password'),
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
        'password' => bcrypt('correct-password'),
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
        'password' => bcrypt('correct-password'),
        'failed_login_count' => 0,
    ]);

    $this->post('/login', [
        'email' => 'counter@example.com',
        'password' => 'wrong-password',
    ]);

    $user->refresh();
    expect($user->failed_login_count)->toBe(1);
});
