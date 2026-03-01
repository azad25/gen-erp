<?php

use App\Models\User;

test('authenticated user can access company setup page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('setup.company'));

    $response->assertOk();
})->skip('Livewire not installed - company setup needs to be migrated to Vue');

test('guest cannot access company setup page', function (): void {
    $response = $this->get(route('setup.company'));

    $response->assertRedirect(route('login'));
})->skip('Livewire not installed - company setup needs to be migrated to Vue');

test('company setup wizard creates company successfully', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('setup.company-setup-wizard')
        ->set('name', 'Test Company')
        ->set('business_type', 'retail')
        ->set('phone', '01712345678')
        ->set('email', 'company@test.com')
        ->call('nextStep')
        ->assertSet('currentStep', 2)
        ->set('address_line1', '123 Test Street')
        ->set('city', 'Dhaka')
        ->set('district', 'Dhaka')
        ->set('postal_code', '1200')
        ->set('vat_registered', false)
        ->call('nextStep')
        ->assertSet('currentStep', 3)
        ->call('submit')
        ->assertRedirect('/');

    $this->assertDatabaseHas('companies', [
        'name' => 'Test Company',
        'business_type' => 'retail',
    ]);

    $this->assertDatabaseHas('company_user', [
        'user_id' => $user->id,
        'role' => 'owner',
        'is_owner' => true,
    ]);

    expect($user->fresh()->last_active_company_id)->not->toBeNull();
})->skip('Livewire not installed - company setup needs to be migrated to Vue');
