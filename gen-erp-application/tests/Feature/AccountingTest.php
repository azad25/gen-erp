<?php

use App\Enums\AccountSubType;
use App\Enums\JournalEntryStatus;
use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PayrollRun;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\CompanyContext;

// ═══════════════════════════════════════════════════
// AccountingTest — 12 tests
// ═══════════════════════════════════════════════════

function seedSystemAccounts(int $companyId): void
{
    $accounts = [
        ['code' => '1001', 'name' => 'Cash in Hand', 'account_type' => 'asset', 'sub_type' => 'cash'],
        ['code' => '1002', 'name' => 'Cash at Bank', 'account_type' => 'asset', 'sub_type' => 'bank'],
        ['code' => '1003', 'name' => 'Accounts Receivable', 'account_type' => 'asset', 'sub_type' => 'receivable'],
        ['code' => '1004', 'name' => 'Inventory', 'account_type' => 'asset', 'sub_type' => 'inventory'],
        ['code' => '2001', 'name' => 'Accounts Payable', 'account_type' => 'liability', 'sub_type' => 'payable'],
        ['code' => '2002', 'name' => 'VAT Payable', 'account_type' => 'liability', 'sub_type' => 'current_liability'],
        ['code' => '2003', 'name' => 'TDS Payable', 'account_type' => 'liability', 'sub_type' => 'current_liability'],
        ['code' => '2004', 'name' => 'Salary Payable', 'account_type' => 'liability', 'sub_type' => 'current_liability'],
        ['code' => '3001', 'name' => 'Owner Capital', 'account_type' => 'equity', 'sub_type' => 'other'],
        ['code' => '4001', 'name' => 'Sales Revenue', 'account_type' => 'income', 'sub_type' => 'revenue'],
        ['code' => '5001', 'name' => 'COGS', 'account_type' => 'expense', 'sub_type' => 'cogs'],
        ['code' => '5002', 'name' => 'Salary Expense', 'account_type' => 'expense', 'sub_type' => 'operating_expense'],
        ['code' => '5005', 'name' => 'Other Expense', 'account_type' => 'expense', 'sub_type' => 'operating_expense'],
    ];

    foreach ($accounts as $a) {
        Account::withoutGlobalScopes()->create(array_merge($a, [
            'company_id' => $companyId,
            'is_system' => true,
            'is_active' => true,
        ]));
    }
}

test('postEntry throws if debits != credits (unbalanced journal)', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedSystemAccounts($company->id);

    $service = app(AccountingService::class);
    $cashAccount = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '1001')->first();
    $revenueAccount = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '4001')->first();

    $entry = $service->createEntry($company, [
        'entry_date' => now(),
        'description' => 'Unbalanced test',
    ], [
        ['account_id' => $cashAccount->id, 'debit' => 10000, 'credit' => 0],
        ['account_id' => $revenueAccount->id, 'debit' => 0, 'credit' => 5000],
    ]);

    $service->postEntry($entry);
})->throws(InvalidArgumentException::class, 'not balanced');

test('journalForInvoice creates correct DR Receivable / CR Revenue entry', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedSystemAccounts($company->id);

    $customer = Customer::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Test Customer',
    ]);
    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Main Warehouse',
        'code' => 'WH-001',
    ]);

    $invoice = Invoice::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_number' => 'INV-001',
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'subtotal' => 100000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 100000,
        'amount_paid' => 0,
        'status' => 'sent',
    ]);

    $service = app(AccountingService::class);
    $entry = $service->journalForInvoice($invoice);
    $entry->refresh();

    expect($entry->status)->toBe(JournalEntryStatus::POSTED);
    expect($entry->isBalanced())->toBeTrue();
    expect($entry->totalDebits())->toBe(100000);

    $lines = $entry->lines;
    $drLine = $lines->firstWhere('debit', '>', 0);
    $crLine = $lines->firstWhere('credit', '>', 0);

    expect($drLine->account->sub_type)->toBe(AccountSubType::RECEIVABLE);
    expect($crLine->account->sub_type)->toBe(AccountSubType::REVENUE);
});

test('journalForPayment creates correct DR Bank / CR Receivable entry', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedSystemAccounts($company->id);

    $customer = Customer::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Test Customer',
    ]);

    $payment = \App\Models\CustomerPayment::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'receipt_number' => 'REC-001',
        'payment_date' => now(),
        'amount' => 50000,
    ]);

    $service = app(AccountingService::class);
    $entry = $service->journalForPayment($payment);
    $entry->refresh();

    expect($entry->isBalanced())->toBeTrue();
    $drLine = $entry->lines->firstWhere('debit', '>', 0);
    expect($drLine->account->sub_type)->toBe(AccountSubType::BANK);
});

test('getBalance returns correct balance considering opening_balance and posted entries', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedSystemAccounts($company->id);

    $cash = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '1001')->first();
    $cash->update(['opening_balance' => 500000]);

    $revenue = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '4001')->first();

    $service = app(AccountingService::class);
    $entry = $service->createEntry($company, [
        'entry_date' => now(),
        'description' => 'Cash sale',
    ], [
        ['account_id' => $cash->id, 'debit' => 100000, 'credit' => 0],
        ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 100000],
    ]);
    $service->postEntry($entry);

    expect($service->getBalance($cash))->toBe(600000);
});

test('getTrialBalance total debits == total credits', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedSystemAccounts($company->id);

    $cash = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '1001')->first();
    $revenue = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '4001')->first();

    $service = app(AccountingService::class);
    $entry = $service->createEntry($company, [
        'entry_date' => now(),
        'description' => 'Test',
    ], [
        ['account_id' => $cash->id, 'debit' => 200000, 'credit' => 0],
        ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 200000],
    ]);
    $service->postEntry($entry);

    $tb = $service->getTrialBalance($company, now());
    expect($tb['total_debit'])->toBe($tb['total_credit']);
});

test('getProfitAndLoss revenue - expenses = correct net profit', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedSystemAccounts($company->id);

    $cash = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '1001')->first();
    $revenue = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '4001')->first();
    $expense = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '5005')->first();

    $service = app(AccountingService::class);

    $e1 = $service->createEntry($company, ['entry_date' => now(), 'description' => 'Sale'], [
        ['account_id' => $cash->id, 'debit' => 300000, 'credit' => 0],
        ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 300000],
    ]);
    $service->postEntry($e1);

    $e2 = $service->createEntry($company, ['entry_date' => now(), 'description' => 'Expense'], [
        ['account_id' => $expense->id, 'debit' => 100000, 'credit' => 0],
        ['account_id' => $cash->id, 'debit' => 0, 'credit' => 100000],
    ]);
    $service->postEntry($e2);

    $pnl = $service->getProfitAndLoss($company, now()->startOfYear(), now());

    expect($pnl['total_income'])->toBe(300000);
    expect($pnl['total_expenses'])->toBe(100000);
    expect($pnl['net_profit'])->toBe(200000);
});

test('getBalanceSheet assets == liabilities + equity', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedSystemAccounts($company->id);

    $cash = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '1001')->first();
    $capital = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '3001')->first();
    $cash->update(['opening_balance' => 1000000]);
    $capital->update(['opening_balance' => 1000000]);

    $service = app(AccountingService::class);
    $bs = $service->getBalanceSheet($company, now());

    expect($bs['balanced'])->toBeTrue();
});

test('Posted journal entry cannot be updated (throws)', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedSystemAccounts($company->id);

    $cash = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '1001')->first();
    $revenue = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '4001')->first();

    $service = app(AccountingService::class);
    $entry = $service->createEntry($company, ['entry_date' => now(), 'description' => 'Test'], [
        ['account_id' => $cash->id, 'debit' => 50000, 'credit' => 0],
        ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 50000],
    ]);
    $service->postEntry($entry);

    $entry = $entry->fresh();
    $entry->description = 'Changed';
    $entry->save();
})->throws(RuntimeException::class, 'cannot be modified');

test('System accounts cannot be deleted', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedSystemAccounts($company->id);

    $systemAccount = Account::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->where('is_system', true)
        ->first();

    expect($systemAccount->is_system)->toBeTrue();
    expect(Account::withoutGlobalScopes()->where('company_id', $company->id)->where('is_system', true)->count())->toBeGreaterThan(0);
});

test('journalForPayroll creates salary expense and payable entries correctly', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedSystemAccounts($company->id);

    $run = PayrollRun::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'run_number' => 'PAY-2026-01-001',
        'period_month' => 1,
        'period_year' => 2026,
        'total_gross' => 500000,
        'total_net' => 400000,
        'total_tax' => 50000,
        'total_deductions' => 100000,
    ]);

    $service = app(AccountingService::class);
    $entry = $service->journalForPayroll($run);
    $entry->refresh();

    expect($entry->status)->toBe(JournalEntryStatus::POSTED);
    expect($entry->isBalanced())->toBeTrue();

    $drLine = $entry->lines->firstWhere('debit', '>', 0);
    expect($drLine->debit)->toBe(500000);
});

test('Company A accounts not visible to Company B', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    CompanyContext::setActive($companyA);
    seedSystemAccounts($companyA->id);

    expect(Account::count())->toBeGreaterThan(0);

    CompanyContext::setActive($companyB);
    expect(Account::count())->toBe(0);
});

test('journalForExpense creates correct DR Expense / CR Cash entry', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedSystemAccounts($company->id);

    $expenseAccount = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '5005')->first();
    $cashAccount = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '1001')->first();

    $expense = Expense::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'account_id' => $expenseAccount->id,
        'payment_account_id' => $cashAccount->id,
        'expense_date' => now(),
        'description' => 'Office supplies',
        'amount' => 5000,
        'tax_amount' => 0,
        'total_amount' => 5000,
    ]);

    $service = app(AccountingService::class);
    $entry = $service->journalForExpense($expense);
    $entry->refresh();

    expect($entry->isBalanced())->toBeTrue();
    expect($entry->totalDebits())->toBe(5000);
});
