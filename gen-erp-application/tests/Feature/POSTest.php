<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\POSSession;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\BranchTransferService;
use App\Services\CompanyContext;
use App\Services\POSService;

// ═══════════════════════════════════════════════
// POSTest — 9 tests
// ═══════════════════════════════════════════════

function createBranchWithWarehouse(Company $company, string $name, string $code): Branch
{
    $warehouse = Warehouse::withoutGlobalScopes()->create(['company_id' => $company->id, 'name' => $name.' WH', 'code' => $code.'-WH']);

    return Branch::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => $name,
        'code' => $code,
        'warehouse_id' => $warehouse->id,
        'is_active' => true,
    ]);
}

test('openSession creates session for branch', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $branch = createBranchWithWarehouse($company, 'Dhaka', 'DHK');

    $service = app(POSService::class);
    $session = $service->openSession($branch, $user, 50000);

    expect($session->status)->toBe('open');
    expect($session->branch_id)->toBe($branch->id);
    expect($session->opening_cash)->toBe(50000);
});

test('openSession throws if branch already has open session', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $branch = createBranchWithWarehouse($company, 'Dhaka', 'DHK');

    $service = app(POSService::class);
    $service->openSession($branch, $user, 50000);

    $service->openSession($branch, $user, 10000);
})->throws(RuntimeException::class, 'already has an open');

test('createSale records sale within session', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $branch = createBranchWithWarehouse($company, 'Dhaka', 'DHK');

    $service = app(POSService::class);
    $session = $service->openSession($branch, $user, 50000);

    $sale = $service->createSale($session, [
        ['description' => 'Product A', 'quantity' => 2, 'unit_price' => 10000],
        ['description' => 'Product B', 'quantity' => 1, 'unit_price' => 5000],
    ], ['amount_tendered' => 30000]);

    expect($sale->status)->toBe('completed');
    expect($sale->total_amount)->toBe(25000);
    expect($sale->items)->toHaveCount(2);
});

test('Cannot create sale without open session', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $branch = createBranchWithWarehouse($company, 'Dhaka', 'DHK');

    $service = app(POSService::class);
    $session = $service->openSession($branch, $user, 50000);
    $service->closeSession($session, $user, 50000);

    $service->createSale($session->fresh(), [
        ['description' => 'X', 'quantity' => 1, 'unit_price' => 1000],
    ], ['amount_tendered' => 1000]);
})->throws(RuntimeException::class, 'not open');

test('voidSale marks sale as voided', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $branch = createBranchWithWarehouse($company, 'Dhaka', 'DHK');

    $service = app(POSService::class);
    $session = $service->openSession($branch, $user, 50000);

    $sale = $service->createSale($session, [
        ['description' => 'X', 'quantity' => 1, 'unit_price' => 5000],
    ], ['amount_tendered' => 5000]);

    $service->voidSale($sale);
    $sale->refresh();

    expect($sale->status)->toBe('voided');
});

test('closeSession calculates cash_difference correctly', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $branch = createBranchWithWarehouse($company, 'Dhaka', 'DHK');

    $service = app(POSService::class);
    $session = $service->openSession($branch, $user, 50000);

    $service->createSale($session, [
        ['description' => 'X', 'quantity' => 1, 'unit_price' => 10000],
    ], ['amount_tendered' => 10000]);

    // Expected: 50000 (opening) + 10000 (sales) = 60000
    // Actual closing: 58000 → difference -2000
    $service->closeSession($session, $user, 58000);
    $session->refresh();

    expect($session->status)->toBe('closed');
    expect($session->expected_cash)->toBe(60000);
    expect($session->cash_difference)->toBe(-2000);
});

test('getSessionSummary returns correct totals', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $branch = createBranchWithWarehouse($company, 'Dhaka', 'DHK');

    $service = app(POSService::class);
    $session = $service->openSession($branch, $user, 50000);

    $service->createSale($session, [['description' => 'A', 'quantity' => 1, 'unit_price' => 10000]], ['amount_tendered' => 10000]);
    $service->createSale($session, [['description' => 'B', 'quantity' => 1, 'unit_price' => 20000]], ['amount_tendered' => 20000]);

    $summary = $service->getSessionSummary($session);

    expect($summary['total_sales'])->toBe(30000);
    expect($summary['sale_count'])->toBe(2);
    expect($summary['average_sale'])->toBe(15000);
});

test('Company A POS sessions not visible to Company B', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();
    $user = User::factory()->create();

    $branchA = createBranchWithWarehouse($companyA, 'A Branch', 'AB');

    $service = app(POSService::class);
    $service->openSession($branchA, $user, 50000);

    CompanyContext::setActive($companyA);
    expect(POSSession::count())->toBe(1);

    CompanyContext::setActive($companyB);
    expect(POSSession::count())->toBe(0);
});

test('BranchTransferService throws when fromBranch == toBranch', function (): void {
    $company = Company::factory()->create();
    $branch = createBranchWithWarehouse($company, 'Dhaka', 'DHK');

    $service = app(BranchTransferService::class);
    $service->createTransfer($branch, $branch, [['product_id' => 1, 'quantity' => 10]]);
})->throws(RuntimeException::class, 'same branch');
