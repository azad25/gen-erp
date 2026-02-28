<?php

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\EntityAlias;
use App\Models\User;
use App\Services\CompanyContext;

test('company A records are not visible to company B users', function (): void {
    // Setup Company A with owner
    $userA = User::factory()->create();
    $companyA = Company::factory()->create(['name' => 'Company A']);
    CompanyUser::factory()->owner()->create([
        'company_id' => $companyA->id,
        'user_id' => $userA->id,
    ]);

    // Setup Company B with owner
    $userB = User::factory()->create();
    $companyB = Company::factory()->create(['name' => 'Company B']);
    CompanyUser::factory()->owner()->create([
        'company_id' => $companyB->id,
        'user_id' => $userB->id,
    ]);

    // Create an EntityAlias record in Company A's context
    CompanyContext::setActive($companyA);
    EntityAlias::create([
        'company_id' => $companyA->id,
        'entity_key' => 'customer',
        'alias' => 'Client A',
    ]);

    // Switch to Company B's context — should NOT see Company A's alias
    CompanyContext::setActive($companyB);
    $aliases = EntityAlias::all();

    expect($aliases)->toHaveCount(0);
});

test('global scope is applied on BelongsToCompany models', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    // Create aliases in both companies (bypassing scopes for setup)
    EntityAlias::withoutGlobalScopes()->insert([
        ['company_id' => $companyA->id, 'entity_key' => 'product', 'alias' => 'Item A', 'created_at' => now(), 'updated_at' => now()],
        ['company_id' => $companyB->id, 'entity_key' => 'product', 'alias' => 'Item B', 'created_at' => now(), 'updated_at' => now()],
    ]);

    // With Company A context, only A's records are visible
    CompanyContext::setActive($companyA);
    $aliasesA = EntityAlias::all();
    expect($aliasesA)->toHaveCount(1);
    expect($aliasesA->first()->alias)->toBe('Item A');
});

test('data still exists when queried without global scopes', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    EntityAlias::withoutGlobalScopes()->insert([
        ['company_id' => $companyA->id, 'entity_key' => 'order', 'alias' => 'PO', 'created_at' => now(), 'updated_at' => now()],
        ['company_id' => $companyB->id, 'entity_key' => 'order', 'alias' => 'Work Order', 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Without global scopes, both records are visible
    $all = EntityAlias::withoutGlobalScopes()->get();
    expect($all)->toHaveCount(2);
});
