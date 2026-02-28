<?php

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Customer;
use App\Models\SavedReport;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\ReportBuilderService;

// ── Report Builder ──────────────────────────────────────────

test('report builder returns results for customer entity', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    // Create some customers
    Customer::factory()->count(5)->create(['company_id' => $company->id]);

    $report = SavedReport::create([
        'company_id' => $company->id,
        'name' => 'All Customers',
        'entity_type' => 'customer',
        'selected_fields' => ['id', 'name', 'email'],
        'filters' => [],
        'created_by' => $user->id,
    ]);

    $service = app(ReportBuilderService::class);
    $result = $service->run($report);

    expect($result)->toHaveKeys(['columns', 'rows', 'total']);
    expect($result['total'])->toBe(5);
    expect($result['columns'])->toContain('name');
});

test('report builder applies filters', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    Customer::factory()->create(['company_id' => $company->id, 'name' => 'Alpha Corp']);
    Customer::factory()->create(['company_id' => $company->id, 'name' => 'Beta Ltd']);

    $report = SavedReport::create([
        'company_id' => $company->id,
        'name' => 'Alpha Search',
        'entity_type' => 'customer',
        'selected_fields' => ['id', 'name'],
        'filters' => [['field' => 'name', 'operator' => 'contains', 'value' => 'Alpha']],
        'created_by' => $user->id,
    ]);

    $service = app(ReportBuilderService::class);
    $result = $service->run($report);

    expect($result['total'])->toBe(1);
});

test('report builder exports CSV file', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    Customer::factory()->count(3)->create(['company_id' => $company->id]);

    $report = SavedReport::create([
        'company_id' => $company->id,
        'name' => 'Export Test',
        'entity_type' => 'customer',
        'selected_fields' => ['id', 'name'],
        'filters' => [],
        'created_by' => $user->id,
    ]);

    $service = app(ReportBuilderService::class);
    $path = $service->export($report, 'csv');

    expect($path)->not->toBeEmpty();
    expect(file_exists($path))->toBeTrue();

    // Cleanup
    @unlink($path);
});

test('report builder returns empty for unknown entity', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    $report = SavedReport::create([
        'company_id' => $company->id,
        'name' => 'Unknown Entity',
        'entity_type' => 'nonexistent',
        'selected_fields' => ['id'],
        'filters' => [],
        'created_by' => $user->id,
    ]);

    $service = app(ReportBuilderService::class);
    $result = $service->run($report);

    expect($result['total'])->toBe(0);
});

// ── Report Builder Fields ───────────────────────────────────

test('available fields include entity-specific fields', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    $service = app(ReportBuilderService::class);

    $customerFields = $service->getAvailableFields('customer');
    $keys = collect($customerFields)->pluck('key')->all();
    expect($keys)->toContain('name', 'email', 'phone');

    $productFields = $service->getAvailableFields('product');
    $keys = collect($productFields)->pluck('key')->all();
    expect($keys)->toContain('name', 'sku', 'selling_price');
});
