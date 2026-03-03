<?php

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;

test('fix access route sets correct company for user', function () {
    // Create user and company
    $user = User::factory()->create();
    $company = Company::factory()->create();
    
    // Attach user to company
    $user->companies()->attach($company->id, [
        'role' => 'owner',
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);
    
    // Login as user
    $this->actingAs($user);
    
    // Visit fix access route
    $response = $this->get('/fix-company-access');
    
    // Should redirect to dashboard
    $response->assertRedirect('/dashboard');
    
    // Session should have active company
    expect(session('active_company_id'))->toBe($company->id);
    
    // User's last active company should be updated
    expect($user->fresh()->last_active_company_id)->toBe($company->id);
});

test('company selection page shows available companies', function () {
    // Create user and multiple companies
    $user = User::factory()->create();
    $company1 = Company::factory()->create(['name' => 'Company One']);
    $company2 = Company::factory()->create(['name' => 'Company Two']);
    
    // Attach user to both companies
    $user->companies()->attach($company1->id, [
        'role' => 'owner',
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);
    
    $user->companies()->attach($company2->id, [
        'role' => 'admin',
        'is_owner' => false,
        'is_active' => true,
        'joined_at' => now(),
    ]);
    
    // Login as user
    $this->actingAs($user);
    
    // Visit company selection page
    $response = $this->get('/select-company');
    
    // Should show company selection page
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Auth/SelectCompany')
        ->has('companies', 2)
        ->where('companies.0.name', 'Company One')
        ->where('companies.1.name', 'Company Two')
    );
});

test('switch company route changes active company', function () {
    // Create user and multiple companies
    $user = User::factory()->create();
    $company1 = Company::factory()->create(['name' => 'Company One']);
    $company2 = Company::factory()->create(['name' => 'Company Two']);
    
    // Attach user to both companies
    $user->companies()->attach($company1->id, [
        'role' => 'owner',
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);
    
    $user->companies()->attach($company2->id, [
        'role' => 'admin',
        'is_owner' => false,
        'is_active' => true,
        'joined_at' => now(),
    ]);
    
    // Login as user and set initial company
    $this->actingAs($user);
    session(['active_company_id' => $company1->id]);
    
    // Switch to company 2
    $response = $this->post("/switch-company/{$company2->id}");
    
    // Should redirect to dashboard
    $response->assertRedirect('/dashboard');
    
    // Session should have new active company
    expect(session('active_company_id'))->toBe($company2->id);
    
    // User's last active company should be updated
    expect($user->fresh()->last_active_company_id)->toBe($company2->id);
});