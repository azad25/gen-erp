<?php

use App\Models\Company;
use App\Models\User;

// ═══════════════════════════════════════════════════
// Company Switch Tests
// ═══════════════════════════════════════════════════

test('user can switch to a company they belong to', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company1 = Company::factory()->create(['name' => 'Company 1']);
    $company2 = Company::factory()->create(['name' => 'Company 2']);

    $user->companies()->attach($company1->id, ['is_active' => true]);
    $user->companies()->attach($company2->id, ['is_active' => true]);

    session(['active_company_id' => $company1->id]);
    $this->actingAs($user);

    $response = $this->postJson("/api/switch-company/{$company2->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Company switched successfully.',
        ])
        ->assertJsonStructure([
            'success',
            'data' => [
                'company' => ['id', 'name'],
                'permissions',
                'subscription',
            ],
            'message',
        ]);

    // Verify session was updated
    expect(session('active_company_id'))->toBe($company2->id);

    // Verify user's last_active_company_id was updated
    $user->refresh();
    expect($user->last_active_company_id)->toBe($company2->id);
});

test('user cannot switch to company they do not belong to', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    $user->companies()->attach($company1->id, ['is_active' => true]);
    // User is NOT a member of company2

    session(['active_company_id' => $company1->id]);
    $this->actingAs($user);

    $response = $this->postJson("/api/switch-company/{$company2->id}");

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'You do not have access to this company.',
        ]);

    // Session should not be updated
    expect(session('active_company_id'))->toBe($company1->id);
});

test('user cannot switch to inactive company', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company1 = Company::factory()->create(['is_active' => true]);
    $company2 = Company::factory()->create(['is_active' => false]); // Inactive

    $user->companies()->attach($company1->id, ['is_active' => true]);
    $user->companies()->attach($company2->id, ['is_active' => true]);

    session(['active_company_id' => $company1->id]);
    $this->actingAs($user);

    $response = $this->postJson("/api/switch-company/{$company2->id}");

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Company not found or inactive.',
        ]);

    // Session should not be updated
    expect(session('active_company_id'))->toBe($company1->id);
});

test('user cannot switch to non-existent company', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company = Company::factory()->create();

    $user->companies()->attach($company->id, ['is_active' => true]);

    session(['active_company_id' => $company->id]);
    $this->actingAs($user);

    $response = $this->postJson('/api/switch-company/99999');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Company not found or inactive.',
        ]);
});

test('company switch regenerates session', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    $user->companies()->attach($company1->id, ['is_active' => true]);
    $user->companies()->attach($company2->id, ['is_active' => true]);

    session(['active_company_id' => $company1->id]);
    $this->actingAs($user);

    $initialSessionId = session()->getId();

    $this->postJson("/api/switch-company/{$company2->id}");

    // Session ID should be different (regenerated)
    expect(session()->getId())->not->toBe($initialSessionId);
});

test('unauthenticated user cannot switch company', function (): void {
    $company = Company::factory()->create();

    $response = $this->postJson("/api/switch-company/{$company->id}");

    $response->assertStatus(401);
});

test('company switch returns user permissions for new company', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    $user->companies()->attach($company1->id, ['is_active' => true]);
    $user->companies()->attach($company2->id, ['is_active' => true]);

    session(['active_company_id' => $company1->id]);
    $this->actingAs($user);

    $response = $this->postJson("/api/switch-company/{$company2->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'permissions',
            ],
        ]);

    // Permissions should be an array
    expect($response->json('data.permissions'))->toBeArray();
});

test('user cannot switch to company with inactive membership', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    $user->companies()->attach($company1->id, ['is_active' => true]);
    $user->companies()->attach($company2->id, ['is_active' => false]); // Inactive membership

    session(['active_company_id' => $company1->id]);
    $this->actingAs($user);

    $response = $this->postJson("/api/switch-company/{$company2->id}");

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'You do not have access to this company.',
        ]);
});
