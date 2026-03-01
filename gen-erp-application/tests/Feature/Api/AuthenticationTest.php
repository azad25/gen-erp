<?php

use App\Models\Company;
use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    // Clear rate limiters before each test
    RateLimiter::clear('login:127.0.0.1');
});

// ═══════════════════════════════════════════════════
// Login Tests
// ═══════════════════════════════════════════════════

test('user can login with valid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
        'email_verified_at' => now(),
    ]);

    $company = Company::factory()->create();
    $user->companies()->attach($company->id, [
        'role' => 'admin',
        'is_active' => true,
    ]);

    // Get CSRF cookie first
    $this->get('/sanctum/csrf-cookie');

    $response = $this->postJson('/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Login successful.',
        ])
        ->assertJsonStructure([
            'success',
            'data' => [
                'user' => ['id', 'name', 'email'],
                'requires_company_selection',
            ],
            'message',
        ]);

    $this->assertAuthenticatedAs($user);
});

test('login fails with invalid credentials', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $this->get('/sanctum/csrf-cookie');

    $response = $this->postJson('/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'These credentials do not match our records.',
        ]);

    $this->assertGuest();
});

test('login fails when email is not verified', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
        'email_verified_at' => null, // Not verified
    ]);

    $this->get('/sanctum/csrf-cookie');

    $response = $this->postJson('/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Please verify your email address first.',
            'requires_verification' => true,
        ]);

    $this->assertGuest();
});

test('login is rate limited after 5 attempts', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $this->get('/sanctum/csrf-cookie');

    // Make 5 failed attempts
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    // 6th attempt should be rate limited
    $response = $this->postJson('/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(429)
        ->assertJsonStructure([
            'success',
            'message',
            'retry_after',
        ]);
});

test('account is locked after 10 failed login attempts', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
        'email_verified_at' => now(),
    ]);

    $this->get('/sanctum/csrf-cookie');

    // Simulate 10 failed attempts by directly updating the user
    $user->update(['failed_login_count' => 10, 'locked_until' => now()->addMinutes(30)]);

    $response = $this->postJson('/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonPath('message', fn($message) => str_contains($message, 'locked'));
});

test('login with single company auto-selects company', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
        'email_verified_at' => now(),
    ]);

    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['is_active' => true]);

    $this->get('/sanctum/csrf-cookie');

    $response = $this->postJson('/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'requires_company_selection' => false,
            ],
        ]);

    expect(session('active_company_id'))->toBe($company->id);
});

test('login with multiple companies requires company selection', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
        'email_verified_at' => now(),
    ]);

    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();
    $user->companies()->attach($company1->id, ['is_active' => true]);
    $user->companies()->attach($company2->id, ['is_active' => true]);

    $this->get('/sanctum/csrf-cookie');

    $response = $this->postJson('/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'requires_company_selection' => true,
            ],
        ]);
});

test('login with 2FA enabled returns 2FA challenge', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
        'email_verified_at' => now(),
        'two_factor_confirmed' => true,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
    ]);

    $this->get('/sanctum/csrf-cookie');

    $response = $this->postJson('/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'two_factor_required' => true,
        ]);

    // User should not be authenticated yet
    $this->assertGuest();
    expect(session('two_factor_user_id'))->toBe($user->id);
});

// ═══════════════════════════════════════════════════
// Logout Tests
// ═══════════════════════════════════════════════════

test('authenticated user can logout', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    $response = $this->postJson('/auth/logout');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);

    $this->assertGuest();
});

test('unauthenticated user cannot logout', function (): void {
    $response = $this->postJson('/auth/logout');

    $response->assertStatus(401);
});

// ═══════════════════════════════════════════════════
// Get User Tests
// ═══════════════════════════════════════════════════

test('authenticated user can get their info', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['is_active' => true]);

    session(['active_company_id' => $company->id]);
    $this->actingAs($user);

    $response = $this->getJson('/auth/user');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'data' => [
                'user' => ['id', 'name', 'email'],
                'company' => ['id', 'name'],
                'permissions',
                'subscription',
            ],
        ]);
});

test('unauthenticated user cannot get user info', function (): void {
    $response = $this->getJson('/auth/user');

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
        ]);
});

test('get user returns null company when no active company', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    $response = $this->getJson('/auth/user');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'company' => null,
                'permissions' => [],
            ],
        ]);
});

// ═══════════════════════════════════════════════════
// Validation Tests
// ═══════════════════════════════════════════════════

test('login requires email', function (): void {
    $this->get('/sanctum/csrf-cookie');

    $response = $this->postJson('/auth/login', [
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('login requires password', function (): void {
    $this->get('/sanctum/csrf-cookie');

    $response = $this->postJson('/auth/login', [
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('login requires valid email format', function (): void {
    $this->get('/sanctum/csrf-cookie');

    $response = $this->postJson('/auth/login', [
        'email' => 'invalid-email',
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

// ═══════════════════════════════════════════════════
// Session Management Tests
// ═══════════════════════════════════════════════════

test('login regenerates session to prevent session fixation', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
        'email_verified_at' => now(),
    ]);

    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['is_active' => true]);

    $this->get('/sanctum/csrf-cookie');

    // Get initial session ID
    $initialSessionId = session()->getId();

    $this->postJson('/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    // Session ID should be different after login
    expect(session()->getId())->not->toBe($initialSessionId);
});

test('logout invalidates session', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    $initialSessionId = session()->getId();

    $this->postJson('/auth/logout');

    // Session should be invalidated
    expect(session()->getId())->not->toBe($initialSessionId);
});
