<?php

use App\Enums\TaxType;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\TaxGroup;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\TaxCalculationService;
use App\Services\TaxResult;
use Database\Seeders\TaxGroupSeeder;

// ── Tax Calculation ─────────────────────────────────────────

test('simple VAT calculation at 15%', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    $taxGroup = TaxGroup::create([
        'company_id' => $company->id,
        'name' => 'VAT 15%',
        'rate' => 15.00,
        'rate_basis_points' => 1500,
        'type' => 'vat',
        'is_compound' => false,
        'is_default' => true,
        'is_active' => true,
    ]);

    $service = new TaxCalculationService();
    $result = $service->calculate(100000, $taxGroup); // ৳1,000.00

    expect($result)->toBeInstanceOf(TaxResult::class);
    expect($result->subtotal)->toBe(100000);
    expect($result->vatAmount)->toBe(15000);    // 15% of 100000
    expect($result->sdAmount)->toBe(0);
    expect($result->totalTax)->toBe(15000);
    expect($result->grandTotal)->toBe(115000);
});

test('compound SD + VAT calculation', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    $sdGroup = TaxGroup::create([
        'company_id' => $company->id,
        'name' => 'SD 20%',
        'rate' => 20.00,
        'rate_basis_points' => 2000,
        'type' => 'sd',
        'is_compound' => false,
        'is_default' => false,
        'is_active' => true,
    ]);

    $vatGroup = TaxGroup::create([
        'company_id' => $company->id,
        'name' => 'VAT 15%',
        'rate' => 15.00,
        'rate_basis_points' => 1500,
        'type' => 'vat',
        'is_compound' => true,
        'is_default' => true,
        'is_active' => true,
    ]);

    $service = new TaxCalculationService();
    $result = $service->calculate(100000, $sdGroup, $vatGroup);

    // SD = 20% of 100000 = 20000
    expect($result->sdAmount)->toBe(20000);
    // VAT on SD-inclusive: 15% of (100000 + 20000) = 15% of 120000 = 18000
    expect($result->vatAmount)->toBe(18000);
    expect($result->totalTax)->toBe(38000);
    expect($result->grandTotal)->toBe(138000);
});

test('zero-rated tax returns zero tax', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    $taxGroup = TaxGroup::create([
        'company_id' => $company->id,
        'name' => 'Zero Rated',
        'rate' => 0.00,
        'rate_basis_points' => 0,
        'type' => 'vat',
        'is_compound' => false,
        'is_default' => false,
        'is_active' => true,
    ]);

    $service = new TaxCalculationService();
    $result = $service->calculate(50000, $taxGroup);

    expect($result->totalTax)->toBe(0);
    expect($result->grandTotal)->toBe(50000);
});

test('TDS calculation deducts from supplier payment', function (): void {
    $service = new TaxCalculationService();
    $result = $service->calculateTds(100000, 500); // 5% TDS

    expect($result['tds_amount'])->toBe(5000);
    expect($result['net_payment'])->toBe(95000);
    expect($result['tds_rate_percent'])->toBe(5.0);
});

// ── Tax Group Seeder ────────────────────────────────────────

test('tax group seeder creates 7 default groups', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    $groups = TaxGroupSeeder::createForCompany($company->id);

    expect($groups)->toHaveCount(7);

    // Verify the standard rates exist
    $rates = collect($groups)->pluck('rate')->sort()->values()->all();
    expect($rates)->toContain(0.00, 2.00, 5.00, 7.50, 10.00, 15.00);

    // Verify one is default
    $defaults = collect($groups)->where('is_default', true);
    expect($defaults)->toHaveCount(1);
    expect($defaults->first()->rate)->toBe(15.00);
});

// ── Tax Type Enum ───────────────────────────────────────────

test('tax type enum has labels', function (): void {
    expect(TaxType::VAT->label())->toBe('VAT');
    expect(TaxType::SD->label())->toBe('Supplementary Duty (SD)');
    expect(TaxType::AIT->label())->toBe('Advance Income Tax (AIT)');
});

// ── Tax Group Scopes ────────────────────────────────────────

test('tax groups scoped by company', function (): void {
    $userA = User::factory()->create();
    $companyA = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $companyA->id,
        'user_id' => $userA->id,
    ]);

    $userB = User::factory()->create();
    $companyB = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $companyB->id,
        'user_id' => $userB->id,
    ]);

    CompanyContext::setActive($companyA);
    TaxGroup::create([
        'company_id' => $companyA->id,
        'name' => 'A Tax',
        'rate' => 15,
        'type' => 'vat',
    ]);

    CompanyContext::setActive($companyB);
    TaxGroup::create([
        'company_id' => $companyB->id,
        'name' => 'B Tax',
        'rate' => 10,
        'type' => 'vat',
    ]);

    // Company B should not see Company A's tax groups
    $groups = TaxGroup::all();
    expect($groups)->toHaveCount(1);
    expect($groups->first()->name)->toBe('B Tax');
});

test('tax result formatted display', function (): void {
    $result = new TaxResult(
        subtotal: 100000,
        sdAmount: 0,
        vatAmount: 15000,
        aitAmount: 0,
        totalTax: 15000,
        grandTotal: 115000,
    );

    expect($result->formattedGrandTotal())->toBe('৳1,150.00');
    expect($result->formattedTotalTax())->toBe('৳150.00');
});

// ── Breakdown Verification ──────────────────────────────────

test('tax calculation returns detailed breakdown', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    $taxGroup = TaxGroup::create([
        'company_id' => $company->id,
        'name' => 'VAT 5%',
        'rate' => 5.00,
        'rate_basis_points' => 500,
        'type' => 'vat',
        'is_compound' => false,
        'is_default' => false,
        'is_active' => true,
    ]);

    $service = new TaxCalculationService();
    $result = $service->calculate(200000, $taxGroup);

    expect($result->breakdown)->toHaveCount(1);
    expect($result->breakdown[0]['name'])->toBe('VAT 5%');
    expect($result->breakdown[0]['type'])->toBe('vat');
    expect($result->breakdown[0]['rate'])->toBe(5.00);
    expect($result->breakdown[0]['tax_amount'])->toBe(10000);
});
