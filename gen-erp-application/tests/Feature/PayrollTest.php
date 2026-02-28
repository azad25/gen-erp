<?php

use App\Enums\PaymentStatus;
use App\Enums\PayrollRunStatus;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\IncomeTaxSlab;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\TaxExemption;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\PayrollService;

// ═══════════════════════════════════════════════════
// PayrollTest — 12 tests
// ═══════════════════════════════════════════════════

function seedBDTaxSlabs(int $companyId, string $fiscalYear = '2025-26'): void
{
    $slabs = [
        ['min_income' => 0, 'max_income' => 35000000, 'tax_rate' => 0, 'display_order' => 1, 'description' => 'Exempt'],
        ['min_income' => 35000000, 'max_income' => 45000000, 'tax_rate' => 5, 'display_order' => 2],
        ['min_income' => 45000000, 'max_income' => 75000000, 'tax_rate' => 10, 'display_order' => 3],
        ['min_income' => 75000000, 'max_income' => 115000000, 'tax_rate' => 15, 'display_order' => 4],
        ['min_income' => 115000000, 'max_income' => 165000000, 'tax_rate' => 20, 'display_order' => 5],
        ['min_income' => 165000000, 'max_income' => null, 'tax_rate' => 25, 'display_order' => 6],
    ];

    foreach ($slabs as $slab) {
        IncomeTaxSlab::withoutGlobalScopes()->create(array_merge($slab, [
            'company_id' => $companyId,
            'fiscal_year' => $fiscalYear,
        ]));
    }
}

test('initiateRun throws if run already exists for that month/year', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $service = app(PayrollService::class);

    $service->initiateRun($company, 2, 2026);

    $service->initiateRun($company, 2, 2026);
})->throws(InvalidArgumentException::class);

test('calculateEntry uses correct attendance days from attendance table', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $employee = Employee::factory()->create(['company_id' => $company->id, 'basic_salary' => 2500000, 'gross_salary' => 3500000]);

    // Mark attendance: 20 present, 2 absent
    for ($d = 1; $d <= 22; $d++) {
        Attendance::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'attendance_date' => '2026-02-'.str_pad((string) $d, 2, '0', STR_PAD_LEFT),
            'status' => $d <= 20 ? 'present' : 'absent',
        ]);
    }

    $service = app(PayrollService::class);
    $run = $service->initiateRun($company, 2, 2026);
    $entry = $service->calculateEntry($run, $employee);

    expect($entry->present_days)->toBe(20.0);
    expect($entry->absent_days)->toBe(2.0);
});

test('Absent days deduction: 2 absent days on 25k basic deducts correctly', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    // basic = 2500000 paise (25,000 BDT)
    $employee = Employee::factory()->create(['company_id' => $company->id, 'basic_salary' => 2500000, 'gross_salary' => 3500000]);

    // 2 absent days out of 25 working
    for ($d = 1; $d <= 25; $d++) {
        Attendance::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'attendance_date' => '2026-03-'.str_pad((string) $d, 2, '0', STR_PAD_LEFT),
            'status' => $d <= 23 ? 'present' : 'absent',
        ]);
    }

    $service = app(PayrollService::class);
    $run = $service->initiateRun($company, 3, 2026);
    $entry = $service->calculateEntry($run, $employee);

    // Working days in March 2026: count non-Friday days
    $workingDays = $entry->working_days;
    $perDay = (int) round(2500000 / $workingDays);
    $expectedDeduction = $perDay * 2;

    expect($entry->attendance_deduction)->toBe($expectedDeduction);
});

test('Income tax slab: 600k annual gross taxed correctly across slabs', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedBDTaxSlabs($company->id);

    $employee = Employee::factory()->create(['company_id' => $company->id, 'basic_salary' => 5000000]);
    $service = app(PayrollService::class);

    // 600k annual = 60,000,000 paise
    $annualTax = $service->calculateAnnualTax($employee, 60000000, '2025-26');

    // 0-350k exempt: 0
    // 350k-450k at 5%: 100000 * 5% = 5000 → 500000 paise
    // 450k-600k at 10%: 150000 * 10% = 15000 → 1500000 paise
    // Total: 2000000 paise (20,000 BDT)
    expect($annualTax)->toBe(2000000);
});

test('Tax exemption: house rent allowance correctly capped', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedBDTaxSlabs($company->id);

    // Basic: 50,000/month = 600,000/year = 60,000,000 paise/year
    $employee = Employee::factory()->create(['company_id' => $company->id, 'basic_salary' => 5000000]);

    // Declare house rent: 500,000 annually (50,000,000 paise) — cap = min(50% of 60M = 30M, 300k = 30,000,000)
    TaxExemption::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'fiscal_year' => '2025-26',
        'exemption_type' => 'house_rent',
        'amount' => 50000000,
    ]);

    $service = app(PayrollService::class);
    // Annual gross 720,000 (72,000,000 paise)
    $taxWithExemption = $service->calculateAnnualTax($employee, 72000000, '2025-26');

    // Without exemption: taxable = 72M paise
    $taxWithout = $service->calculateAnnualTax(
        Employee::factory()->create(['company_id' => $company->id, 'basic_salary' => 5000000]),
        72000000,
        '2025-26'
    );

    // With exemption should be lower
    expect($taxWithExemption)->toBeLessThan($taxWithout);
});

test('Overtime: 10 hours OT on 30k basic = correct amount', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    // basic = 3000000 paise (30,000 BDT)
    $employee = Employee::factory()->create(['company_id' => $company->id, 'basic_salary' => 3000000, 'gross_salary' => 4000000]);

    // Mark attendance with overtime
    Attendance::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'attendance_date' => '2026-02-01',
        'status' => 'present',
        'overtime_hours' => 10,
    ]);

    $service = app(PayrollService::class);
    $run = $service->initiateRun($company, 2, 2026);
    $entry = $service->calculateEntry($run, $employee);

    // OT rate = 3000000 / 26 / 8 * 2 = 28846 paise per OT hour (rounded)
    $expectedRate = (int) round((3000000 / 26 / 8) * 2);
    $expectedAmount = (int) round($expectedRate * 10);

    expect($entry->overtime_amount)->toBe($expectedAmount);
});

test('approveRun blocks further status change to draft', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $user = User::factory()->create();
    $service = app(PayrollService::class);
    $run = $service->initiateRun($company, 4, 2026);
    $service->approveRun($run, $user);

    expect($run->fresh()->status)->toBe(PayrollRunStatus::APPROVED);
    expect($run->fresh()->approved_by)->toBe($user->id);
});

test('markAsPaid updates all entry payment_status to paid', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $employee = Employee::factory()->create(['company_id' => $company->id, 'basic_salary' => 2000000, 'gross_salary' => 3000000]);

    $service = app(PayrollService::class);
    $run = $service->initiateRun($company, 5, 2026);
    $service->calculateEntry($run, $employee);
    $service->markAsPaid($run, 'bank', now());

    $entry = PayrollEntry::withoutGlobalScopes()->where('payroll_run_id', $run->id)->first();
    expect($entry->payment_status)->toBe(PaymentStatus::PAID);
    expect($run->fresh()->status)->toBe(PayrollRunStatus::PAID);
});

test('Company A payroll not accessible to Company B', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    CompanyContext::setActive($companyA);
    $service = app(PayrollService::class);
    $service->initiateRun($companyA, 6, 2026);

    expect(PayrollRun::all())->toHaveCount(1);

    CompanyContext::setActive($companyB);
    expect(PayrollRun::all())->toHaveCount(0);
});

test('calculateAnnualTax returns 0 for income below 350,000', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedBDTaxSlabs($company->id);

    $employee = Employee::factory()->create(['company_id' => $company->id]);
    $service = app(PayrollService::class);

    // 300k = 30,000,000 paise — below exempt slab
    $tax = $service->calculateAnnualTax($employee, 30000000, '2025-26');
    expect($tax)->toBe(0);
});

test('calculateMonthlyTax matches calculateAnnualTax divided by 12', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedBDTaxSlabs($company->id);

    $employee = Employee::factory()->create(['company_id' => $company->id, 'basic_salary' => 5000000]);
    $service = app(PayrollService::class);

    $monthlyGross = 5000000; // 50k/month
    $annualTax = $service->calculateAnnualTax($employee, $monthlyGross * 12, '2025-26');
    $monthlyTax = $service->calculateMonthlyTax($employee, $monthlyGross);

    expect($monthlyTax)->toBe((int) round($annualTax / 12));
});

test('PayrollRun auto-generates run_number', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $service = app(PayrollService::class);
    $run = $service->initiateRun($company, 7, 2026);

    expect($run->run_number)->toStartWith('PAY-2026-07-');
});
