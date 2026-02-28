<?php

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\EntityAlias;
use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Support\Facades\Cache;

// ── Entity Alias CRUD + Cache ───────────────────────────────

test('entity alias can be created and cached', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create(['business_type' => 'retail']);
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    EntityAlias::create([
        'company_id' => $company->id,
        'entity_key' => 'customer',
        'alias' => 'Client',
    ]);

    // Clear cache to force reload
    Cache::forget("entity_aliases:{$company->id}");

    $resolved = __entity('customer', false, $company);
    expect($resolved)->toBe('Client');
});

test('entity alias cache is invalidated on update', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create(['business_type' => 'retail']);
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    $alias = EntityAlias::create([
        'company_id' => $company->id,
        'entity_key' => 'supplier',
        'alias' => 'Vendor',
    ]);

    // Warm cache
    Cache::forget("entity_aliases:{$company->id}");
    $first = __entity('supplier', false, $company);
    expect($first)->toBe('Vendor');

    // Update and invalidate
    $alias->update(['alias' => 'Partner']);
    Cache::forget("entity_aliases:{$company->id}");

    $second = __entity('supplier', false, $company);
    expect($second)->toBe('Partner');
});

test('entity alias falls back to default when no alias exists', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create(['business_type' => 'retail']);
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);
    Cache::forget("entity_aliases:{$company->id}");

    // No alias set — should fall back
    $result = __entity('product', false, $company);
    expect($result)->not->toBeEmpty();
});

test('entity aliases are scoped by company', function (): void {
    $userA = User::factory()->create();
    $companyA = Company::factory()->create(['business_type' => 'retail']);
    CompanyUser::factory()->owner()->create([
        'company_id' => $companyA->id,
        'user_id' => $userA->id,
    ]);

    $userB = User::factory()->create();
    $companyB = Company::factory()->create(['business_type' => 'retail']);
    CompanyUser::factory()->owner()->create([
        'company_id' => $companyB->id,
        'user_id' => $userB->id,
    ]);

    CompanyContext::setActive($companyA);
    EntityAlias::create([
        'company_id' => $companyA->id,
        'entity_key' => 'customer',
        'alias' => 'Buyer',
    ]);

    CompanyContext::setActive($companyB);
    $aliases = EntityAlias::all();
    expect($aliases)->toHaveCount(0);
});

// ── Alert Rule Model Exists ─────────────────────────────────

test('alert rule can be created with all fields', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    $rule = \App\Models\AlertRule::create([
        'company_id' => $company->id,
        'name' => 'Low Stock Alert',
        'entity_type' => 'product',
        'trigger_field' => 'stock_quantity',
        'operator' => 'less_than',
        'trigger_value' => '10',
        'channels' => ['in_app', 'email'],
        'target_roles' => ['owner', 'warehouse'],
        'message_template' => 'Stock for {name} is below {stock_quantity}',
        'is_active' => true,
    ]);

    expect($rule->exists)->toBeTrue();
    expect($rule->channels)->toContain('in_app');
    expect($rule->target_roles)->toContain('warehouse');
});
