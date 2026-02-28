<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Supplier;
use App\Services\CompanyContext;
use App\Services\ContactService;
use Carbon\Carbon;

// ═══════════════════════════════════════════════════
// CustomerTest — 9 tests
// ═══════════════════════════════════════════════════

test('customer created with correct company_id and auto-generated customer_code', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(ContactService::class);
    $customer = $service->createCustomer($company, ['name' => 'Rahim Traders']);

    expect($customer->company_id)->toBe($company->id);
    expect($customer->customer_code)->toStartWith('CUST-');
    expect(Customer::all())->toHaveCount(1);
});

test('customer custom fields save and retrieve correctly', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    \App\Models\CustomFieldDefinition::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'entity_type' => 'customer',
        'label' => 'NID Number',
        'field_key' => 'nid_number',
        'field_type' => 'text',
        'is_filterable' => false,
        'is_required' => false,
        'is_active' => true,
    ]);

    $service = app(ContactService::class);
    $customer = $service->createCustomer(
        $company,
        ['name' => 'Karim Supplies'],
        ['nid_number' => '1234567890']
    );

    $cfService = app(\App\Services\CustomFieldService::class);
    $values = $cfService->getValues('customer', $customer->id);

    expect($values)->toHaveKey('nid_number');
    expect($values['nid_number']->value_text)->toBe('1234567890');
});

test('currentBalance returns opening_balance when no transactions', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $customer = Customer::factory()->withOpeningBalance(500000)->create(['company_id' => $company->id]);

    expect($customer->currentBalance())->toBe(500000); // ৳5,000.00
});

test('currentBalance reflects recorded transactions', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $customer = Customer::factory()->withOpeningBalance(100000)->create(['company_id' => $company->id]);

    $service = app(ContactService::class);
    $service->recordCustomerTransaction($customer, 'invoice', 50000, 'Invoice #001');
    $service->recordCustomerTransaction($customer, 'payment', -30000, 'Payment received');

    // opening 100000 + invoice 50000 + payment -30000 = 120000
    expect($customer->currentBalance())->toBe(120000);
});

test('isOverCreditLimit returns true when balance exceeds credit_limit', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $customer = Customer::factory()
        ->withOpeningBalance(200000) // ৳2,000
        ->withCreditLimit(100000)   // ৳1,000
        ->create(['company_id' => $company->id]);

    expect($customer->isOverCreditLimit())->toBeTrue();
});

test('isOverCreditLimit returns false when no credit limit set', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $customer = Customer::factory()
        ->withOpeningBalance(500000) // ৳5,000
        ->create(['company_id' => $company->id]); // credit_limit = 0

    expect($customer->isOverCreditLimit())->toBeFalse();
});

test('Company A cannot see Company B customers', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    Customer::factory()->create(['company_id' => $companyA->id]);
    Customer::factory()->create(['company_id' => $companyB->id]);
    Customer::factory()->create(['company_id' => $companyB->id]);

    CompanyContext::setActive($companyA);
    expect(Customer::all())->toHaveCount(1);

    CompanyContext::setActive($companyB);
    expect(Customer::all())->toHaveCount(2);
});

test('customer import creates records and returns error summary', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(ContactService::class);
    $result = $service->importContacts($company, 'customer', [
        ['name' => 'Customer A'],
        ['name' => 'Customer B'],
        ['name' => ''],  // will fail — name required
    ]);

    expect($result['created'])->toBeGreaterThanOrEqual(2);
    expect($result)->toHaveKeys(['created', 'failed', 'errors']);
});

test('customer statement returns correct transactions for date range', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $customer = Customer::factory()->withOpeningBalance(0)->create(['company_id' => $company->id]);

    $service = app(ContactService::class);
    // Jan txn
    CustomerTransaction::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'type' => 'invoice',
        'amount' => 10000,
        'balance_after' => 10000,
        'description' => 'Jan Invoice',
        'transaction_date' => '2026-01-15',
        'created_at' => now(),
    ]);
    // Feb txn
    CustomerTransaction::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'type' => 'payment',
        'amount' => -5000,
        'balance_after' => 5000,
        'description' => 'Feb Payment',
        'transaction_date' => '2026-02-10',
        'created_at' => now(),
    ]);

    // Statement for Feb only
    $statement = $service->getCustomerStatement(
        $customer,
        Carbon::parse('2026-02-01'),
        Carbon::parse('2026-02-28')
    );

    expect($statement['transactions'])->toHaveCount(1); // only Feb txn
    expect($statement['opening_balance'])->toBe(10000); // Jan invoice as prior
    expect($statement['closing_balance'])->toBe(5000);  // 10000 - 5000
});

// ═══════════════════════════════════════════════════
// SupplierTest — 4 tests
// ═══════════════════════════════════════════════════

test('supplier created with auto-generated supplier_code', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(ContactService::class);
    $supplier = $service->createSupplier($company, ['name' => 'ABC Textiles']);

    expect($supplier->company_id)->toBe($company->id);
    expect($supplier->supplier_code)->toStartWith('SUPP-');
});

test('calculateTdsVds returns correct deductions', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(ContactService::class);
    $supplier = Supplier::factory()
        ->withTds(5.0)
        ->withVds(2.0)
        ->create(['company_id' => $company->id]);

    $result = $service->calculateTdsVds($supplier, 100000); // ৳1,000.00 gross

    expect($result['tds_amount'])->toBe(5000);  // 5%
    expect($result['vds_amount'])->toBe(2000);  // 2%
    expect($result['net'])->toBe(93000);          // 100000 - 5000 - 2000
});

test('supplier TDS 5% on 10000: net = 9500, tds = 500', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $supplier = Supplier::factory()
        ->withTds(5.0)
        ->create(['company_id' => $company->id]);

    $result = $supplier->netPaymentAmount(10000);

    expect($result['tds_amount'])->toBe(500);
    expect($result['vds_amount'])->toBe(0);
    expect($result['net'])->toBe(9500);
});

test('Company B cannot see Company A suppliers', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    Supplier::factory()->create(['company_id' => $companyA->id]);
    Supplier::factory()->create(['company_id' => $companyA->id]);
    Supplier::factory()->create(['company_id' => $companyB->id]);

    CompanyContext::setActive($companyA);
    expect(Supplier::all())->toHaveCount(2);

    CompanyContext::setActive($companyB);
    expect(Supplier::all())->toHaveCount(1);
});
