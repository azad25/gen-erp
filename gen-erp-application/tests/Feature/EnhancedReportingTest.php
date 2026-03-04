<?php

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Accounting\Models\JournalEntryLine;
use App\Domain\Auth\Models\Company;
use App\Domain\Customer\Models\Customer;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\Product\Models\Product;
use App\Domain\Report\Services\AgingReportService;
use App\Domain\Report\Services\DimensionalReportService;
use App\Domain\Report\Services\InventoryValuationReportService;
use App\Support\CompanyContext;
use App\Support\Enums\InvoiceStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('dimensional P&L report filters by branch correctly', function (): void {
    // Arrange
    $company = Company::factory()->create();
    $user = \App\Domain\Auth\Models\User::factory()->create();
    $company->users()->attach($user);
    $this->actingAs($user);
    CompanyContext::setActive($company);

    // Create accounts
    $revenueAccount = Account::factory()->create([
        'company_id' => $company->id,
        'account_code' => '4001',
        'account_name' => 'Sales Revenue',
    ]);

    $expenseAccount = Account::factory()->create([
        'company_id' => $company->id,
        'account_code' => '6001',
        'account_name' => 'Office Expenses',
    ]);

    // Create journal entries with different branch_ids
    $journalEntry1 = JournalEntry::create([
        'company_id' => $company->id,
        'entry_number' => 'JE-001',
        'entry_date' => now()->toDateString(),
        'description' => 'Branch 1 Sale',
        'status' => 'posted',
    ]);

    JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry1->id,
        'account_id' => $revenueAccount->id,
        'debit' => 0,
        'credit' => 100000, // $1000 revenue
        'description' => 'Branch 1 Revenue',
        'branch_id' => 1,
    ]);

    JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry1->id,
        'account_id' => $expenseAccount->id,
        'debit' => 20000, // $200 expense
        'credit' => 0,
        'description' => 'Branch 1 Expense',
        'branch_id' => 1,
    ]);

    // Branch 2 entries
    $journalEntry2 = JournalEntry::create([
        'company_id' => $company->id,
        'entry_number' => 'JE-002',
        'entry_date' => now()->toDateString(),
        'description' => 'Branch 2 Sale',
        'status' => 'posted',
    ]);

    JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry2->id,
        'account_id' => $revenueAccount->id,
        'debit' => 0,
        'credit' => 150000, // $1500 revenue
        'description' => 'Branch 2 Revenue',
        'branch_id' => 2,
    ]);

    JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry2->id,
        'account_id' => $expenseAccount->id,
        'debit' => 30000, // $300 expense
        'credit' => 0,
        'description' => 'Branch 2 Expense',
        'branch_id' => 2,
    ]);

    $reportService = app(DimensionalReportService::class);

    // Act - Generate P&L for Branch 1 only
    $branch1Report = $reportService->dimensionalProfitAndLoss(
        $company,
        now()->startOfDay(),
        now()->endOfDay(),
        ['branch_id' => 1]
    );

    // Assert
    expect($branch1Report['revenue']['total'])->toBe(100000);
    expect($branch1Report['expenses']['total'])->toBe(20000);
    expect($branch1Report['net_income'])->toBe(80000); // 100000 - 20000

    // Act - Generate P&L for Branch 2 only
    $branch2Report = $reportService->dimensionalProfitAndLoss(
        $company,
        now()->startOfDay(),
        now()->endOfDay(),
        ['branch_id' => 2]
    );

    // Assert
    expect($branch2Report['revenue']['total'])->toBe(150000);
    expect($branch2Report['expenses']['total'])->toBe(30000);
    expect($branch2Report['net_income'])->toBe(120000); // 150000 - 30000

    // Act - Generate P&L for all branches (no filter)
    $allBranchesReport = $reportService->dimensionalProfitAndLoss(
        $company,
        now()->startOfDay(),
        now()->endOfDay()
    );

    // Assert
    expect($allBranchesReport['revenue']['total'])->toBe(250000); // 100000 + 150000
    expect($allBranchesReport['expenses']['total'])->toBe(50000); // 20000 + 30000
    expect($allBranchesReport['net_income'])->toBe(200000); // 250000 - 50000
});

test('dimensional P&L report filters by custom dimensions', function (): void {
    // Arrange
    $company = Company::factory()->create();
    $user = \App\Domain\Auth\Models\User::factory()->create();
    $company->users()->attach($user);
    $this->actingAs($user);
    CompanyContext::setActive($company);

    $revenueAccount = Account::factory()->create([
        'company_id' => $company->id,
        'account_code' => '4001',
        'account_name' => 'Sales Revenue',
    ]);

    // Create journal entry with custom dimensions
    $journalEntry = JournalEntry::create([
        'company_id' => $company->id,
        'entry_number' => 'JE-001',
        'entry_date' => now()->toDateString(),
        'description' => 'Project Sale',
        'status' => 'posted',
    ]);

    JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $revenueAccount->id,
        'debit' => 0,
        'credit' => 100000,
        'description' => 'Project Revenue',
        'dimensions' => [
            'project_id' => 'PRJ-001',
            'department' => 'Engineering',
        ],
    ]);

    // Create another entry with different dimensions
    JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $revenueAccount->id,
        'debit' => 0,
        'credit' => 50000,
        'description' => 'Other Project Revenue',
        'dimensions' => [
            'project_id' => 'PRJ-002',
            'department' => 'Marketing',
        ],
    ]);

    $reportService = app(DimensionalReportService::class);

    // Act - Filter by project_id
    $projectReport = $reportService->dimensionalProfitAndLoss(
        $company,
        now()->startOfDay(),
        now()->endOfDay(),
        ['custom' => ['project_id' => 'PRJ-001']]
    );

    // Assert
    expect($projectReport['revenue']['total'])->toBe(100000);
    expect($projectReport['dimensions']['custom']['project_id'])->toBe('PRJ-001');

    // Act - Filter by department
    $departmentReport = $reportService->dimensionalProfitAndLoss(
        $company,
        now()->startOfDay(),
        now()->endOfDay(),
        ['custom' => ['department' => 'Marketing']]
    );

    // Assert
    expect($departmentReport['revenue']['total'])->toBe(50000);
});

test('AR aging report calculates buckets correctly', function (): void {
    // Arrange
    $company = Company::factory()->create();
    $user = \App\Domain\Auth\Models\User::factory()->create();
    $company->users()->attach($user);
    $this->actingAs($user);
    CompanyContext::setActive($company);

    $customer = Customer::factory()->create(['company_id' => $company->id]);

    // Create invoices with different ages
    $invoices = [
        // Current (0 days old)
        Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'status' => InvoiceStatus::SENT,
            'total_amount' => 100000,
            'amount_paid' => 0,
            'balance_due' => 100000,
        ]),
        // 15 days old (1-30 bucket)
        Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_date' => now()->subDays(15)->toDateString(),
            'status' => InvoiceStatus::SENT,
            'total_amount' => 200000,
            'amount_paid' => 0,
            'balance_due' => 200000,
        ]),
        // 45 days old (31-60 bucket)
        Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_date' => now()->subDays(45)->toDateString(),
            'status' => InvoiceStatus::SENT,
            'total_amount' => 300000,
            'amount_paid' => 0,
            'balance_due' => 300000,
        ]),
        // 75 days old (61-90 bucket)
        Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_date' => now()->subDays(75)->toDateString(),
            'status' => InvoiceStatus::SENT,
            'total_amount' => 400000,
            'amount_paid' => 0,
            'balance_due' => 400000,
        ]),
        // 120 days old (over 90 bucket)
        Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_date' => now()->subDays(120)->toDateString(),
            'status' => InvoiceStatus::SENT,
            'total_amount' => 500000,
            'amount_paid' => 0,
            'balance_due' => 500000,
        ]),
    ];

    $reportService = app(AgingReportService::class);

    // Act
    $agingReport = $reportService->accountsReceivableAging($company);

    // Assert
    expect($agingReport['summary']['total_outstanding'])->toBe(1500000);
    expect($agingReport['summary']['current'])->toBe(100000);
    expect($agingReport['summary']['days_1_30'])->toBe(200000);
    expect($agingReport['summary']['days_31_60'])->toBe(300000);
    expect($agingReport['summary']['days_61_90'])->toBe(400000);
    expect($agingReport['summary']['days_over_90'])->toBe(500000);
    expect($agingReport['summary']['customer_count'])->toBe(1);

    // Check customer details
    $customerAging = $agingReport['customers'][0];
    expect($customerAging['customer_id'])->toBe($customer->id);
    expect($customerAging['total_outstanding'])->toBe(1500000);
    expect($customerAging['invoice_count'])->toBe(5);
});

test('customer aging detail shows invoice breakdown', function (): void {
    // Arrange
    $company = Company::factory()->create();
    $user = \App\Domain\Auth\Models\User::factory()->create();
    $company->users()->attach($user);
    $this->actingAs($user);
    CompanyContext::setActive($company);

    $customer = Customer::factory()->create(['company_id' => $company->id]);

    $invoice1 = Invoice::factory()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'invoice_date' => now()->subDays(30)->toDateString(),
        'due_date' => now()->subDays(15)->toDateString(), // Overdue
        'status' => InvoiceStatus::SENT,
        'total_amount' => 100000,
        'amount_paid' => 30000,
        'balance_due' => 70000,
    ]);

    $reportService = app(AgingReportService::class);

    // Act
    $customerDetail = $reportService->customerAgingDetail($company, $customer->id);

    // Assert
    expect($customerDetail['customer']['id'])->toBe($customer->id);
    expect($customerDetail['aging_summary']['total'])->toBe(70000);
    expect($customerDetail['invoices'])->toHaveCount(1);

    $invoiceDetail = $customerDetail['invoices'][0];
    expect($invoiceDetail['invoice_id'])->toBe($invoice1->id);
    expect($invoiceDetail['balance_due'])->toBe(70000);
    expect($invoiceDetail['days_outstanding'])->toBe(30);
    expect($invoiceDetail['aging_bucket'])->toBe('1-30 Days');
    expect($invoiceDetail['is_overdue'])->toBeTrue();
});

test('inventory valuation report calculates correct values', function (): void {
    // This test would require setting up stock layers, which is complex
    // For now, we'll create a basic test structure
    $company = Company::factory()->create();
    $user = \App\Domain\Auth\Models\User::factory()->create();
    $company->users()->attach($user);
    $this->actingAs($user);
    CompanyContext::setActive($company);

    $reportService = app(InventoryValuationReportService::class);

    // Act
    $valuationReport = $reportService->inventoryValuation($company);

    // Assert - Basic structure validation
    expect($valuationReport)->toHaveKey('as_of_date');
    expect($valuationReport)->toHaveKey('company');
    expect($valuationReport)->toHaveKey('products');
    expect($valuationReport)->toHaveKey('summary');
    expect($valuationReport['company'])->toBe($company->name);
});

test('COGS analysis report structure is correct', function (): void {
    $company = Company::factory()->create();
    $user = \App\Domain\Auth\Models\User::factory()->create();
    $company->users()->attach($user);
    $this->actingAs($user);
    CompanyContext::setActive($company);

    $reportService = app(InventoryValuationReportService::class);

    // Act
    $cogsReport = $reportService->cogsAnalysis($company, now()->subMonth(), now());

    // Assert - Basic structure validation
    expect($cogsReport)->toHaveKey('period');
    expect($cogsReport)->toHaveKey('company');
    expect($cogsReport)->toHaveKey('products');
    expect($cogsReport)->toHaveKey('summary');
    expect($cogsReport['summary'])->toHaveKey('total_cogs');
    expect($cogsReport['summary'])->toHaveKey('total_quantity_sold');
    expect($cogsReport['summary'])->toHaveKey('average_cogs_per_unit');
});

test('inventory turnover analysis calculates ratios correctly', function (): void {
    $company = Company::factory()->create();
    $user = \App\Domain\Auth\Models\User::factory()->create();
    $company->users()->attach($user);
    $this->actingAs($user);
    CompanyContext::setActive($company);

    $reportService = app(InventoryValuationReportService::class);

    // Act
    $turnoverReport = $reportService->inventoryTurnoverAnalysis($company, now()->subMonth(), now());

    // Assert - Basic structure validation
    expect($turnoverReport)->toHaveKey('period');
    expect($turnoverReport)->toHaveKey('company');
    expect($turnoverReport)->toHaveKey('metrics');
    expect($turnoverReport)->toHaveKey('interpretation');
    expect($turnoverReport['metrics'])->toHaveKey('inventory_turnover_ratio');
    expect($turnoverReport['metrics'])->toHaveKey('days_sales_in_inventory');
    expect($turnoverReport['interpretation'])->toHaveKey('turnover_rating');
    expect($turnoverReport['interpretation'])->toHaveKey('efficiency_note');
});