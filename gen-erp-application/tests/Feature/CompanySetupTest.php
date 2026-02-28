<?php

use App\Models\User;
use Livewire\Livewire;

test('authenticated user can access company setup page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('setup.company'));

    $response->assertOk();
    $response->assertSeeLivewire('setup.company-setup-wizard');
});

test('guest cannot access company setup page', function (): void {
    $response = $this->get(route('setup.company'));

    $response->assertRedirect(route('login'));
});

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
        ->assertRedirect('/app');

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
});
