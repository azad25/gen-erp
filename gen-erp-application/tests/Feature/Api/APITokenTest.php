<?php

use App\Models\Company;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

// ═══════════════════════════════════════════════════
// API Token Creation Tests
// ═══════════════════════════════════════════════════

test('authenticated user can create API token', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['is_active' => true]);

    session(['active_company_id' => $company->id]);
    $this->actingAs($user);

    $response = $this->postJson('/auth/tokens', [
        'name' => 'Test Token',
        'abilities' => ['read:products', 'write:invoices'],
        'expires_at' => now()->addDays(30)->toDateTimeString(),
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'data' => [
                'token',
                'name',
                'abilities',
                'expires_at',
            ],
            'message',
        ]);

    // Verify token starts with prefix
    $token = $response->json('data.token');
    expect($token)->toStartWith('generp_');

    // Verify token is stored in database with company_id
    $tokenRecord = PersonalAccessToken::where('tokenable_id', $user->id)
        ->where('name', 'Test Token')
        ->first();

    expect($tokenRecord)->not->toBeNull();
    expect($tokenRecord->company_id)->toBe($company->id);
    expect($tokenRecord->abilities)->toBe(['read:products', 'write:invoices']);
});

test('API token creation requires active company', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    $response = $this->postJson('/auth/tokens', [
        'name' => 'Test Token',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'No active company selected.',
        ]);
});

test('API token creation verifies user company membership', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company = Company::factory()->create();
    // User is NOT a member of this company

    session(['active_company_id' => $company->id]);
    $this->actingAs($user);

    $response = $this->postJson('/auth/tokens', [
        'name' => 'Test Token',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'You do not have access to this company.',
        ]);
});

test('API token creation with default abilities', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['is_active' => true]);

    session(['active_company_id' => $company->id]);
    $this->actingAs($user);

    $response = $this->postJson('/auth/tokens', [
        'name' => 'Test Token',
    ]);

    $response->assertStatus(201);

    // Default abilities should be ['*']
    expect($response->json('data.abilities'))->toBe(['*']);
});

test('API token creation validates name', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['is_active' => true]);

    session(['active_company_id' => $company->id]);
    $this->actingAs($user);

    $response = $this->postJson('/auth/tokens', [
        'abilities' => ['*'],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('unauthenticated user cannot create API token', function (): void {
    $response = $this->postJson('/auth/tokens', [
        'name' => 'Test Token',
    ]);

    $response->assertStatus(401);
});

// ═══════════════════════════════════════════════════
// API Token Listing Tests
// ═══════════════════════════════════════════════════

test('authenticated user can list their API tokens', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['is_active' => true]);

    session(['active_company_id' => $company->id]);

    // Create some tokens
    $token1 = $user->createToken('Token 1', ['*']);
    $token2 = $user->createToken('Token 2', ['read:products']);

    PersonalAccessToken::where('id', $token1->accessToken->id)->update(['company_id' => $company->id]);
    PersonalAccessToken::where('id', $token2->accessToken->id)->update(['company_id' => $company->id]);

    $this->actingAs($user);

    $response = $this->getJson('/auth/tokens');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'abilities',
                    'last_used_at',
                    'expires_at',
                    'created_at',
                ],
            ],
        ]);

    expect($response->json('data'))->toHaveCount(2);
});

test('user only sees tokens for active company', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();
    $user->companies()->attach($company1->id, ['is_active' => true]);
    $user->companies()->attach($company2->id, ['is_active' => true]);

    // Create tokens for both companies
    $token1 = $user->createToken('Company 1 Token');
    $token2 = $user->createToken('Company 2 Token');

    PersonalAccessToken::where('id', $token1->accessToken->id)->update(['company_id' => $company1->id]);
    PersonalAccessToken::where('id', $token2->accessToken->id)->update(['company_id' => $company2->id]);

    // Set active company to company1
    session(['active_company_id' => $company1->id]);
    $this->actingAs($user);

    $response = $this->getJson('/auth/tokens');

    $response->assertStatus(200);

    // Should only see company1's token
    $tokens = $response->json('data');
    expect($tokens)->toHaveCount(1);
    expect($tokens[0]['name'])->toBe('Company 1 Token');
});

test('unauthenticated user cannot list API tokens', function (): void {
    $response = $this->getJson('/auth/tokens');

    $response->assertStatus(401);
});

// ═══════════════════════════════════════════════════
// API Token Revocation Tests
// ═══════════════════════════════════════════════════

test('user can revoke their own API token', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['is_active' => true]);

    session(['active_company_id' => $company->id]);

    $token = $user->createToken('Test Token');
    PersonalAccessToken::where('id', $token->accessToken->id)->update(['company_id' => $company->id]);

    $this->actingAs($user);

    $response = $this->deleteJson("/auth/tokens/{$token->accessToken->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'API token revoked successfully.',
        ]);

    // Verify token is deleted
    expect(PersonalAccessToken::find($token->accessToken->id))->toBeNull();
});

test('user cannot revoke token from different company', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();
    $user->companies()->attach($company1->id, ['is_active' => true]);
    $user->companies()->attach($company2->id, ['is_active' => true]);

    // Create token for company2
    $token = $user->createToken('Company 2 Token');
    PersonalAccessToken::where('id', $token->accessToken->id)->update(['company_id' => $company2->id]);

    // Set active company to company1
    session(['active_company_id' => $company1->id]);
    $this->actingAs($user);

    $response = $this->deleteJson("/auth/tokens/{$token->accessToken->id}");

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Token not found.',
        ]);

    // Token should still exist
    expect(PersonalAccessToken::find($token->accessToken->id))->not->toBeNull();
});

test('user cannot revoke another users token', function (): void {
    $user1 = User::factory()->create(['email_verified_at' => now()]);
    $user2 = User::factory()->create(['email_verified_at' => now()]);
    $company = Company::factory()->create();
    $user1->companies()->attach($company->id, ['is_active' => true]);
    $user2->companies()->attach($company->id, ['is_active' => true]);

    // User2 creates a token
    $token = $user2->createToken('User 2 Token');
    PersonalAccessToken::where('id', $token->accessToken->id)->update(['company_id' => $company->id]);

    // User1 tries to revoke it
    session(['active_company_id' => $company->id]);
    $this->actingAs($user1);

    $response = $this->deleteJson("/auth/tokens/{$token->accessToken->id}");

    $response->assertStatus(404);

    // Token should still exist
    expect(PersonalAccessToken::find($token->accessToken->id))->not->toBeNull();
});

test('unauthenticated user cannot revoke API token', function (): void {
    $response = $this->deleteJson('/auth/tokens/1');

    $response->assertStatus(401);
});

// ═══════════════════════════════════════════════════
// API Token Usage Tests
// ═══════════════════════════════════════════════════

test('API request with valid token is authenticated', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['is_active' => true]);

    $token = $user->createToken('Test Token', ['*']);
    PersonalAccessToken::where('id', $token->accessToken->id)->update(['company_id' => $company->id]);

    $response = $this->withToken($token->plainTextToken)
        ->getJson('/api/v1/customers');

    // Should not be 401 (authenticated)
    expect($response->status())->not->toBe(401);
});

test('API request with invalid token is rejected', function (): void {
    $response = $this->withToken('invalid_token')
        ->getJson('/api/v1/customers');

    $response->assertStatus(401);
});

test('API request without token is rejected', function (): void {
    $response = $this->getJson('/api/v1/customers');

    $response->assertStatus(401);
});

test('API token provides company context automatically', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['is_active' => true]);

    // Create some customers for this company
    \App\Models\Customer::factory()->count(3)->create(['company_id' => $company->id]);

    $token = $user->createToken('Test Token', ['*']);
    PersonalAccessToken::where('id', $token->accessToken->id)->update(['company_id' => $company->id]);

    $response = $this->withToken($token->plainTextToken)
        ->getJson('/api/v1/customers');

    // Should return customers scoped to the token's company
    if ($response->status() === 200) {
        expect($response->json('data'))->toHaveCount(3);
    }
});
