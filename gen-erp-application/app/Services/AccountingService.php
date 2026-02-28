<?php

namespace App\Services;

use App\Enums\AccountSubType;
use App\Enums\AccountType;
use App\Enums\JournalEntryStatus;
use App\Models\Account;
use App\Models\Company;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PayrollRun;
use App\Models\SupplierPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Orchestrates double-entry bookkeeping: journal creation, posting, and financial reports.
 */
class AccountingService
{
    // ═══════════════════════════════════════════════
    // Journal Entry CRUD
    // ═══════════════════════════════════════════════

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array{account_id: int, debit: int, credit: int, description?: string}>  $lines
     */
    public function createEntry(Company $company, array $data, array $lines): JournalEntry
    {
        return DB::transaction(function () use ($company, $data, $lines): JournalEntry {
            $entry = JournalEntry::withoutGlobalScopes()->create(array_merge($data, [
                'company_id' => $company->id,
                'status' => JournalEntryStatus::DRAFT,
            ]));

            foreach ($lines as $line) {
                JournalEntryLine::withoutGlobalScopes()->create(array_merge($line, [
                    'company_id' => $company->id,
                    'journal_entry_id' => $entry->id,
                ]));
            }

            return $entry->load('lines');
        });
    }

    /**
     * Post a journal entry. Validates balanced debits == credits.
     */
    public function postEntry(JournalEntry $entry, ?User $user = null): void
    {
        if (! $entry->isBalanced()) {
            throw new InvalidArgumentException(__('Journal entry is not balanced. Debits: :d, Credits: :c', [
                'd' => $entry->totalDebits(),
                'c' => $entry->totalCredits(),
            ]));
        }

        // Use withoutGlobalScopes to bypass the immutability check on the model
        JournalEntry::withoutGlobalScopes()
            ->where('id', $entry->id)
            ->update([
                'status' => JournalEntryStatus::POSTED,
                'posted_by' => $user?->id,
            ]);
    }

    // ═══════════════════════════════════════════════
    // Auto-journal Creation
    // ═══════════════════════════════════════════════

    /**
     * DR: Accounts Receivable, CR: Sales Revenue, CR: VAT Payable (if VAT).
     */
    public function journalForInvoice(Invoice $invoice): JournalEntry
    {
        $company = Company::withoutGlobalScopes()->find($invoice->company_id);
        $receivable = $this->findSystemAccount($invoice->company_id, AccountSubType::RECEIVABLE);
        $revenue = $this->findSystemAccount($invoice->company_id, AccountSubType::REVENUE);

        $lines = [
            ['account_id' => $receivable->id, 'debit' => $invoice->total_amount, 'credit' => 0, 'description' => 'Accounts Receivable'],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => $invoice->subtotal, 'description' => 'Sales Revenue'],
        ];

        if ($invoice->tax_amount > 0) {
            $vatPayable = $this->findSystemAccount($invoice->company_id, AccountSubType::CURRENT_LIABILITY, '2002');
            $lines[] = ['account_id' => $vatPayable->id, 'debit' => 0, 'credit' => $invoice->tax_amount, 'description' => 'VAT Payable'];
        }

        $entry = $this->createEntry($company, [
            'entry_date' => $invoice->invoice_date ?? now(),
            'reference_type' => 'invoice',
            'reference_id' => $invoice->id,
            'description' => 'Invoice '.$invoice->invoice_number,
            'is_system' => true,
        ], $lines);

        $this->postEntry($entry);

        return $entry;
    }

    /**
     * DR: Bank/Cash, CR: Accounts Receivable.
     */
    public function journalForPayment(CustomerPayment $payment): JournalEntry
    {
        $company = Company::withoutGlobalScopes()->find($payment->company_id);
        $bank = $this->findSystemAccount($payment->company_id, AccountSubType::BANK);
        $receivable = $this->findSystemAccount($payment->company_id, AccountSubType::RECEIVABLE);

        $entry = $this->createEntry($company, [
            'entry_date' => $payment->payment_date ?? now(),
            'reference_type' => 'customer_payment',
            'reference_id' => $payment->id,
            'description' => 'Payment received '.$payment->receipt_number,
            'is_system' => true,
        ], [
            ['account_id' => $bank->id, 'debit' => $payment->amount, 'credit' => 0, 'description' => 'Bank'],
            ['account_id' => $receivable->id, 'debit' => 0, 'credit' => $payment->amount, 'description' => 'Accounts Receivable'],
        ]);

        $this->postEntry($entry);

        return $entry;
    }

    /**
     * DR: Inventory/COGS, DR: VAT Receivable (if VAT), CR: Accounts Payable.
     */
    public function journalForPurchase(GoodsReceipt $receipt): JournalEntry
    {
        $company = Company::withoutGlobalScopes()->find($receipt->company_id);
        $inventory = $this->findSystemAccount($receipt->company_id, AccountSubType::INVENTORY);
        $payable = $this->findSystemAccount($receipt->company_id, AccountSubType::PAYABLE);

        $totalAmount = $receipt->items->sum(fn ($item) => $item->received_quantity * $item->unit_cost);

        $entry = $this->createEntry($company, [
            'entry_date' => $receipt->received_date ?? now(),
            'reference_type' => 'goods_receipt',
            'reference_id' => $receipt->id,
            'description' => 'GRN '.$receipt->grn_number,
            'is_system' => true,
        ], [
            ['account_id' => $inventory->id, 'debit' => $totalAmount, 'credit' => 0, 'description' => 'Inventory'],
            ['account_id' => $payable->id, 'debit' => 0, 'credit' => $totalAmount, 'description' => 'Accounts Payable'],
        ]);

        $this->postEntry($entry);

        return $entry;
    }

    /**
     * DR: Accounts Payable, CR: Bank/Cash, DR: TDS Payable (if TDS).
     */
    public function journalForSupplierPayment(SupplierPayment $payment): JournalEntry
    {
        $company = Company::withoutGlobalScopes()->find($payment->company_id);
        $payable = $this->findSystemAccount($payment->company_id, AccountSubType::PAYABLE);
        $bank = $this->findSystemAccount($payment->company_id, AccountSubType::BANK);

        $lines = [
            ['account_id' => $payable->id, 'debit' => $payment->amount, 'credit' => 0, 'description' => 'Accounts Payable'],
            ['account_id' => $bank->id, 'debit' => 0, 'credit' => $payment->amount - ($payment->tds_amount ?? 0), 'description' => 'Bank'],
        ];

        if (($payment->tds_amount ?? 0) > 0) {
            $tds = $this->findSystemAccount($payment->company_id, AccountSubType::CURRENT_LIABILITY, '2003');
            $lines[] = ['account_id' => $tds->id, 'debit' => 0, 'credit' => $payment->tds_amount, 'description' => 'TDS Payable'];
        }

        $entry = $this->createEntry($company, [
            'entry_date' => $payment->payment_date ?? now(),
            'reference_type' => 'supplier_payment',
            'reference_id' => $payment->id,
            'description' => 'Supplier payment '.$payment->payment_number,
            'is_system' => true,
        ], $lines);

        $this->postEntry($entry);

        return $entry;
    }

    /**
     * DR: Salary Expense, CR: Salary Payable, CR: Tax Payable, CR: Other Deductions.
     */
    public function journalForPayroll(PayrollRun $run): JournalEntry
    {
        $company = Company::withoutGlobalScopes()->find($run->company_id);
        $salaryExpense = $this->findSystemAccount($run->company_id, AccountSubType::OPERATING_EXPENSE, '5002');
        $salaryPayable = $this->findSystemAccount($run->company_id, AccountSubType::CURRENT_LIABILITY, '2004');

        // DR: total gross salary
        $lines = [
            ['account_id' => $salaryExpense->id, 'debit' => $run->total_gross, 'credit' => 0, 'description' => 'Salary Expense'],
        ];

        // CR: net salary payable
        $creditTotal = $run->total_net;
        $lines[] = ['account_id' => $salaryPayable->id, 'debit' => 0, 'credit' => $run->total_net, 'description' => 'Salary Payable'];

        // CR: tax payable
        if ($run->total_tax > 0) {
            $taxPayable = $this->findSystemAccount($run->company_id, AccountSubType::CURRENT_LIABILITY, '2003');
            $lines[] = ['account_id' => $taxPayable->id, 'debit' => 0, 'credit' => $run->total_tax, 'description' => 'Income Tax Payable'];
            $creditTotal += $run->total_tax;
        }

        // CR: remaining deductions to salary payable to balance
        $remainingDeductions = $run->total_gross - $creditTotal;
        if ($remainingDeductions > 0) {
            $lines[] = ['account_id' => $salaryPayable->id, 'debit' => 0, 'credit' => $remainingDeductions, 'description' => 'Other Deductions'];
        }

        $entry = $this->createEntry($company, [
            'entry_date' => $run->payment_date ?? now(),
            'reference_type' => 'payroll_run',
            'reference_id' => $run->id,
            'description' => 'Payroll '.$run->run_number,
            'is_system' => true,
        ], $lines);

        $this->postEntry($entry);

        return $entry;
    }

    /**
     * DR: Expense Account, CR: Cash/Bank.
     */
    public function journalForExpense(Expense $expense): JournalEntry
    {
        $company = Company::withoutGlobalScopes()->find($expense->company_id);

        $expenseAccount = $expense->account_id
            ? Account::withoutGlobalScopes()->find($expense->account_id)
            : $this->findSystemAccount($expense->company_id, AccountSubType::OPERATING_EXPENSE, '5005');

        $paymentAccount = $expense->payment_account_id
            ? Account::withoutGlobalScopes()->find($expense->payment_account_id)
            : $this->findSystemAccount($expense->company_id, AccountSubType::CASH);

        $entry = $this->createEntry($company, [
            'entry_date' => $expense->expense_date ?? now(),
            'reference_type' => 'expense',
            'reference_id' => $expense->id,
            'description' => 'Expense '.$expense->expense_number,
            'is_system' => true,
        ], [
            ['account_id' => $expenseAccount->id, 'debit' => $expense->total_amount, 'credit' => 0, 'description' => $expense->description],
            ['account_id' => $paymentAccount->id, 'debit' => 0, 'credit' => $expense->total_amount, 'description' => 'Payment'],
        ]);

        $this->postEntry($entry);

        return $entry;
    }

    // ═══════════════════════════════════════════════
    // Balance & Reporting
    // ═══════════════════════════════════════════════

    public function getBalance(Account $account, ?Carbon $asOf = null): int
    {
        $query = JournalEntryLine::withoutGlobalScopes()
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', function (Builder $q) use ($asOf): void {
                $q->where('status', 'posted');
                if ($asOf) {
                    $q->where('entry_date', '<=', $asOf);
                }
            });

        $debits = (int) (clone $query)->sum('debit');
        $credits = (int) (clone $query)->sum('credit');

        $netMovement = $account->normalBalanceSide() === 'debit'
            ? $debits - $credits
            : $credits - $debits;

        return $account->opening_balance + $netMovement;
    }

    /**
     * @return array{accounts: array<int, array{code: string, name: string, type: string, debit: int, credit: int}>, total_debit: int, total_credit: int}
     */
    public function getTrialBalance(Company $company, Carbon $asOf): array
    {
        $accounts = Account::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $rows = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $balance = $this->getBalance($account, $asOf);
            if ($balance === 0) {
                continue;
            }

            $debit = $account->normalBalanceSide() === 'debit' ? $balance : 0;
            $credit = $account->normalBalanceSide() === 'credit' ? $balance : 0;

            // Negative balances go to opposite side
            if ($balance < 0) {
                $debit = $account->normalBalanceSide() === 'debit' ? 0 : abs($balance);
                $credit = $account->normalBalanceSide() === 'credit' ? 0 : abs($balance);
            }

            $rows[] = [
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->account_type->value,
                'debit' => $debit,
                'credit' => $credit,
            ];

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        return [
            'accounts' => $rows,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
        ];
    }

    /**
     * @return array{income: array<int, array{name: string, amount: int}>, expenses: array<int, array{name: string, amount: int}>, total_income: int, total_expenses: int, net_profit: int}
     */
    public function getProfitAndLoss(Company $company, Carbon $from, Carbon $to): array
    {
        $incomeAccounts = Account::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('account_type', AccountType::INCOME)
            ->where('is_active', true)
            ->get();

        $expenseAccounts = Account::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('account_type', AccountType::EXPENSE)
            ->where('is_active', true)
            ->get();

        $income = [];
        $totalIncome = 0;
        foreach ($incomeAccounts as $acc) {
            $balance = $this->getBalanceForPeriod($acc, $from, $to);
            if ($balance !== 0) {
                $income[] = ['name' => $acc->name, 'amount' => $balance];
                $totalIncome += $balance;
            }
        }

        $expenses = [];
        $totalExpenses = 0;
        foreach ($expenseAccounts as $acc) {
            $balance = $this->getBalanceForPeriod($acc, $from, $to);
            if ($balance !== 0) {
                $expenses[] = ['name' => $acc->name, 'amount' => $balance];
                $totalExpenses += $balance;
            }
        }

        return [
            'income' => $income,
            'expenses' => $expenses,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_profit' => $totalIncome - $totalExpenses,
        ];
    }

    /**
     * @return array{assets: array<int, array{name: string, balance: int}>, liabilities: array<int, array{name: string, balance: int}>, equity: array<int, array{name: string, balance: int}>, total_assets: int, total_liabilities: int, total_equity: int, balanced: bool}
     */
    public function getBalanceSheet(Company $company, Carbon $asOf): array
    {
        $types = [AccountType::ASSET, AccountType::LIABILITY, AccountType::EQUITY];
        $result = ['assets' => [], 'liabilities' => [], 'equity' => [], 'total_assets' => 0, 'total_liabilities' => 0, 'total_equity' => 0];

        foreach ($types as $type) {
            $accounts = Account::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('account_type', $type)
                ->where('is_active', true)
                ->get();

            $key = match ($type) {
                AccountType::ASSET => 'assets',
                AccountType::LIABILITY => 'liabilities',
                AccountType::EQUITY => 'equity',
            };

            foreach ($accounts as $acc) {
                $balance = $this->getBalance($acc, $asOf);
                if ($balance !== 0) {
                    $result[$key][] = ['name' => $acc->name, 'balance' => $balance];
                    $result['total_'.$key] += $balance;
                }
            }
        }

        // Add net income to equity
        $pnl = $this->getProfitAndLoss($company, Carbon::create($asOf->year, 1, 1), $asOf);
        if ($pnl['net_profit'] !== 0) {
            $result['equity'][] = ['name' => 'Net Profit (Current Year)', 'balance' => $pnl['net_profit']];
            $result['total_equity'] += $pnl['net_profit'];
        }

        $result['balanced'] = $result['total_assets'] === ($result['total_liabilities'] + $result['total_equity']);

        return $result;
    }

    // ─── Helpers ───

    private function findSystemAccount(int $companyId, AccountSubType $subType, ?string $code = null): Account
    {
        $query = Account::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_system', true)
            ->where('sub_type', $subType);

        if ($code) {
            $query->where('code', $code);
        }

        $account = $query->first();

        if (! $account) {
            throw new RuntimeException(__('System account not found: :type', ['type' => $subType->label()]));
        }

        return $account;
    }

    private function getBalanceForPeriod(Account $account, Carbon $from, Carbon $to): int
    {
        $query = JournalEntryLine::withoutGlobalScopes()
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', fn (Builder $q) => $q->where('status', 'posted')
                ->whereBetween('entry_date', [$from, $to]));

        $debits = (int) (clone $query)->sum('debit');
        $credits = (int) (clone $query)->sum('credit');

        return $account->normalBalanceSide() === 'debit'
            ? $debits - $credits
            : $credits - $debits;
    }
}
