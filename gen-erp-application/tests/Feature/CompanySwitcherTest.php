<?php

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use App\Services\CompanyContext;

test('user with two companies can switch active company', function (): void {
    $user = User::factory()->create();
    $companyA = Company::factory()->create(['name' => 'Company A']);
    $companyB = Company::factory()->create(['name' => 'Company B']);

    CompanyUser::factory()->owner()->create([
        'company_id' => $companyA->id,
        'user_id' => $user->id,
    ]);
    CompanyUser::factory()->create([
        'company_id' => $companyB->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);

    $this->actingAs($user);
    CompanyContext::setActive($companyA);

    $response = $this->post(route('company.switch', $companyB->id));

    $response->assertRedirect();
    expect(CompanyContext::activeId())->toBe($companyB->id);
});

test('user cannot switch to a company they do not belong to', function (): void {
    $user = User::factory()->create();
    $ownCompany = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    CompanyUser::factory()->owner()->create([
        'company_id' => $ownCompany->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);
    CompanyContext::setActive($ownCompany);

    $response = $this->post(route('company.switch', $otherCompany->id));

    $response->assertForbidden();
});
