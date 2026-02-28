<?php

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\EntityAlias;
use App\Models\User;
use App\Services\CompanyContext;

test('user can create company after registration', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);

    expect($company)->toBeInstanceOf(Company::class);
    expect($company->name)->not->toBeEmpty();
});

test('company assigned to user as owner', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);

    $pivot = CompanyUser::where('company_id', $company->id)
        ->where('user_id', $user->id)
        ->first();

    expect($pivot->is_owner)->toBeTrue();
    expect($pivot->role)->toBe(CompanyRole::OWNER);
});

test('pharmacy company has Patient alias for customer entity', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->pharmacy()->create();

    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);

    CompanyContext::setActive($company);

    // Apply templates
    $templateService = app(\App\Services\BusinessTypeTemplateService::class);
    $templateService->apply($company);

    $alias = EntityAlias::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->where('entity_key', 'customer')
        ->first();

    expect($alias)->not->toBeNull();
    expect($alias->alias)->toBe('Patient');
});
