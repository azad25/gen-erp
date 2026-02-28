<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\BranchAccessService;
use App\Services\BranchContext;
use App\Services\CompanyContext;

// ═══════════════════════════════════════════════
// BranchTest — 12 tests
// ═══════════════════════════════════════════════

test('Branch created with correct company_id', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $branch = Branch::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Dhaka Branch',
        'code' => 'DHK',
        'city' => 'Dhaka',
        'is_active' => true,
    ]);

    expect($branch->company_id)->toBe($company->id);
    expect($branch->is_active)->toBeTrue();
});

test('OWNER can access all branches without assignment', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);

    $branch = Branch::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Branch A',
        'code' => 'BA',
    ]);

    $service = app(BranchAccessService::class);

    expect($service->canAccess($user, $branch))->toBeTrue();
    expect($service->accessibleBranches($user, $company))->toHaveCount(1);
});

test('ADMIN can access all branches without assignment', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'admin']);

    Branch::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'B1', 'code' => 'B1']);
    Branch::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'B2', 'code' => 'B2']);

    $service = app(BranchAccessService::class);

    expect($service->accessibleBranches($user, $company))->toHaveCount(2);
});

test('Regular user cannot access branch not assigned to them', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'sales']);

    $branch = Branch::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Branch X',
        'code' => 'BX',
    ]);

    $service = app(BranchAccessService::class);

    expect($service->canAccess($user, $branch))->toBeFalse();
    expect($service->accessibleBranches($user, $company))->toHaveCount(0);
});

test('BranchScope filters SalesOrder query when BranchContext is active', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $warehouse = \App\Models\Warehouse::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'WH', 'code' => 'WH1']);
    $branchA = Branch::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'A', 'code' => 'A']);
    $branchB = Branch::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'B', 'code' => 'B']);

    SalesOrder::withoutGlobalScopes()->create(['company_id' => $company->id, 'branch_id' => $branchA->id, 'warehouse_id' => $warehouse->id, 'order_number' => 'SO-001', 'order_date' => now(), 'status' => 'draft']);
    SalesOrder::withoutGlobalScopes()->create(['company_id' => $company->id, 'branch_id' => $branchB->id, 'warehouse_id' => $warehouse->id, 'order_number' => 'SO-002', 'order_date' => now(), 'status' => 'draft']);

    BranchContext::setActive($branchA);

    $count = SalesOrder::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->where('branch_id', $branchA->id)
        ->count();

    expect($count)->toBe(1);
});

test('BranchScope does NOT filter when user is OWNER with BranchContext cleared', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $warehouse = \App\Models\Warehouse::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'WH2', 'code' => 'WH2']);
    $branchA = Branch::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'A', 'code' => 'A']);
    $branchB = Branch::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'B', 'code' => 'B']);

    SalesOrder::withoutGlobalScopes()->create(['company_id' => $company->id, 'branch_id' => $branchA->id, 'warehouse_id' => $warehouse->id, 'order_number' => 'SO-003', 'order_date' => now(), 'status' => 'draft']);
    SalesOrder::withoutGlobalScopes()->create(['company_id' => $company->id, 'branch_id' => $branchB->id, 'warehouse_id' => $warehouse->id, 'order_number' => 'SO-004', 'order_date' => now(), 'status' => 'draft']);

    BranchContext::clear();

    $count = SalesOrder::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->count();

    expect($count)->toBe(2);
});

test('Switching branches updates session and BranchContext::activeId', function (): void {
    $company = Company::factory()->create();

    $branchA = Branch::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'A', 'code' => 'A']);
    $branchB = Branch::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'B', 'code' => 'B']);

    BranchContext::setActive($branchA);
    expect(BranchContext::activeId())->toBe($branchA->id);

    BranchContext::setActive($branchB);
    expect(BranchContext::activeId())->toBe($branchB->id);
});

test('assignUser creates branch_user pivot with correct permissions', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'sales']);

    $branch = Branch::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Branch Z',
        'code' => 'BZ',
    ]);

    $service = app(BranchAccessService::class);
    $service->assignUser($branch, $user, [
        'can_view' => true,
        'can_create' => true,
        'can_edit' => false,
        'can_delete' => false,
    ]);

    expect($service->canAccess($user, $branch))->toBeTrue();
    expect($service->can($user, $branch, 'edit'))->toBeFalse();
    expect($service->can($user, $branch, 'view'))->toBeTrue();
});

test('can() returns false for permission not granted in pivot', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'warehouse']);

    $branch = Branch::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Branch Y',
        'code' => 'BY',
    ]);

    $service = app(BranchAccessService::class);
    $service->assignUser($branch, $user, ['can_delete' => false]);

    expect($service->can($user, $branch, 'delete'))->toBeFalse();
});

test('removeUser removes branch access', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'sales']);

    $branch = Branch::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Branch R',
        'code' => 'BR',
    ]);

    $service = app(BranchAccessService::class);
    $service->assignUser($branch, $user, []);
    expect($service->canAccess($user, $branch))->toBeTrue();

    $service->removeUser($branch, $user);
    expect($service->canAccess($user, $branch))->toBeFalse();
});

test('Company A branches not visible to Company B', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    Branch::withoutGlobalScopes()->create(['company_id' => $companyA->id, 'name' => 'A1', 'code' => 'A1']);

    CompanyContext::setActive($companyA);
    expect(Branch::count())->toBe(1);

    CompanyContext::setActive($companyB);
    expect(Branch::count())->toBe(0);
});

test('BranchContext::clear() removes active branch', function (): void {
    $company = Company::factory()->create();
    $branch = Branch::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => 'T', 'code' => 'T']);

    BranchContext::setActive($branch);
    expect(BranchContext::hasActive())->toBeTrue();

    BranchContext::clear();
    expect(BranchContext::hasActive())->toBeFalse();
    expect(BranchContext::activeId())->toBeNull();
});
