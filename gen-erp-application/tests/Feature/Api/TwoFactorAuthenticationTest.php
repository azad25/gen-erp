<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

// ═══════════════════════════════════════════════════
// 2FA Enable Tests
// ═══════════════════════════════════════════════════

test('authenticated user can enable 2FA', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    $response = $this->postJson('/auth/two-factor/enable');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'data' => [
                'secret',
                'qr_code_url',
            ],
            'message',
        ]);

    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull();
    expect($user->two_factor_confirmed)->toBeFalse(); // Not confirmed yet
});

test('unauthenticated user cannot enable 2FA', function (): void {
    $response = $this->postJson('/auth/two-factor/enable');

    $response->assertStatus(401);
});

test('cannot enable 2FA when already enabled', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'two_factor_confirmed' => true,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
    ]);
    $this->actingAs($user);

    $response = $this->postJson('/auth/two-factor/enable');

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Two-factor authentication is already enabled.',
        ]);
});

// ═══════════════════════════════════════════════════
// 2FA Confirm Tests
// ═══════════════════════════════════════════════════

test('user can confirm 2FA with valid code', function (): void {
    $google2fa = new Google2FA();
    $secret = $google2fa->generateSecretKey();

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed' => false,
    ]);
    $this->actingAs($user);

    // Generate a valid TOTP code
    $validCode = $google2fa->getCurrentOtp($secret);

    $response = $this->postJson('/auth/two-factor/confirm', [
        'code' => $validCode,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'data' => [
                'recovery_codes',
            ],
            'message',
        ]);

    // Check recovery codes
    $recoveryCodes = $response->json('data.recovery_codes');
    expect($recoveryCodes)->toHaveCount(10);

    $user->refresh();
    expect($user->two_factor_confirmed)->toBeTrue();
    expect($user->two_factor_recovery_codes)->not->toBeNull();
});

test('2FA confirm fails with invalid code', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed' => false,
    ]);
    $this->actingAs($user);

    $response = $this->postJson('/auth/two-factor/confirm', [
        'code' => '000000', // Invalid code
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Invalid authentication code. Please try again.',
        ]);

    $user->refresh();
    expect($user->two_factor_confirmed)->toBeFalse();
});

test('2FA confirm requires code', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    $response = $this->postJson('/auth/two-factor/confirm', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

test('2FA confirm fails when 2FA not enabled', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    $response = $this->postJson('/auth/two-factor/confirm', [
        'code' => '123456',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Two-factor authentication has not been enabled yet.',
        ]);
});

// ═══════════════════════════════════════════════════
// 2FA Challenge Tests
// ═══════════════════════════════════════════════════

test('user can complete 2FA challenge with valid code', function (): void {
    $google2fa = new Google2FA();
    $secret = $google2fa->generateSecretKey();

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
        'email_verified_at' => now(),
        'two_factor_confirmed' => true,
        'two_factor_secret' => encrypt($secret),
    ]);

    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['is_active' => true]);

    // Simulate partial login
    session(['two_factor_user_id' => $user->id]);

    // Generate valid code
    $validCode = $google2fa->getCurrentOtp($secret);

    $response = $this->postJson('/auth/two-factor/challenge', [
        'code' => $validCode,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Two-factor authentication successful.',
        ])
        ->assertJsonStructure([
            'success',
            'data' => [
                'user',
            ],
            'message',
        ]);

    $this->assertAuthenticatedAs($user);
    expect(session('two_factor_user_id'))->toBeNull();
});

test('2FA challenge fails with invalid code', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'two_factor_confirmed' => true,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
    ]);

    session(['two_factor_user_id' => $user->id]);

    $response = $this->postJson('/auth/two-factor/challenge', [
        'code' => '000000',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Invalid authentication code.',
        ]);

    $this->assertGuest();
});

test('2FA challenge works with recovery code', function (): void {
    $recoveryCode = 'ABCDEF1234';
    $hashedCode = Hash::make($recoveryCode);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'two_factor_confirmed' => true,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_recovery_codes' => encrypt(json_encode([$hashedCode])),
    ]);

    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['is_active' => true]);

    session(['two_factor_user_id' => $user->id]);

    $response = $this->postJson('/auth/two-factor/challenge', [
        'recovery_code' => $recoveryCode,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $this->assertAuthenticatedAs($user);

    // Recovery code should be invalidated
    $user->refresh();
    $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);
    expect($codes)->toBeEmpty();
});

test('2FA challenge fails with invalid recovery code', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'two_factor_confirmed' => true,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_recovery_codes' => encrypt(json_encode([Hash::make('VALIDCODE')])),
    ]);

    session(['two_factor_user_id' => $user->id]);

    $response = $this->postJson('/auth/two-factor/challenge', [
        'recovery_code' => 'INVALIDCODE',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Invalid recovery code.',
        ]);

    $this->assertGuest();
});

test('2FA challenge fails when no pending challenge', function (): void {
    $response = $this->postJson('/auth/two-factor/challenge', [
        'code' => '123456',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'No pending 2FA challenge.',
        ]);
});

// ═══════════════════════════════════════════════════
// 2FA Disable Tests
// ═══════════════════════════════════════════════════

test('user can disable 2FA with valid password', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'password' => 'password123',
        'two_factor_confirmed' => true,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
    ]);
    $this->actingAs($user);

    $response = $this->postJson('/auth/two-factor/disable', [
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Two-factor authentication has been disabled.',
        ]);

    $user->refresh();
    expect($user->two_factor_confirmed)->toBeFalse();
    expect($user->two_factor_secret)->toBeNull();
    expect($user->two_factor_recovery_codes)->toBeNull();
});

test('2FA disable fails with invalid password', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'password' => 'password123',
        'two_factor_confirmed' => true,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
    ]);
    $this->actingAs($user);

    $response = $this->postJson('/auth/two-factor/disable', [
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Invalid password.',
        ]);

    $user->refresh();
    expect($user->two_factor_confirmed)->toBeTrue();
});

test('2FA disable requires password', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    $response = $this->postJson('/auth/two-factor/disable', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('unauthenticated user cannot disable 2FA', function (): void {
    $response = $this->postJson('/auth/two-factor/disable', [
        'password' => 'password123',
    ]);

    $response->assertStatus(401);
});
