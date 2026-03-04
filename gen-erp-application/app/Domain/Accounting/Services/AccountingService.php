<?php

namespace App\Domain\Accounting\Services;

use App\Support\Enums\AccountSubType;
use App\Support\Enums\AccountType;
use App\Support\Enums\JournalCode;
use App\Support\Enums\JournalEntryStatus;
use App\Domain\Accounting\Actions\CreateAccountAction;
use App\Domain\Accounting\Actions\DeleteAccountAction;
use App\Domain\Accounting\Actions\UpdateAccountAction;
use App\Domain\Accounting\Contracts\AccountingServiceInterface;
use App\Domain\Accounting\DTOs\CreateAccountData;
use App\Domain\Accounting\DTOs\ProposedJournalEntry;
use App\Domain\Accounting\DTOs\ProposedJournalLine;
use App\Domain\Accounting\DTOs\UpdateAccountData;
use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\PaymentMethod;
use App\Domain\Accounting\Models\AccountGroup;
use App\Domain\Auth\Models\Company;
use App\Domain\Customer\Models\CustomerPayment;
use App\Domain\Accounting\Models\Expense;
use App\Models\GoodsReceipt;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Accounting\Models\JournalEntryLine;
use App\Domain\HR\Models\PayrollRun;
use App\Models\SupplierPayment;
use App\Domain\Auth\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Orchestrates double-entry bookkeeping: journal creation, posting, and financial reports.
 */
class AccountingService implements AccountingServiceInterface
{
    public function __construct(
        private CreateAccountAction $createAccountAction,
        private UpdateAccountAction $updateAccountAction,
        private DeleteAccountAction $deleteAccountAction,
        private PostingService $postingService,
    ) {}

    // ═══════════════════════════════════════════════
    // Account Management
    // ═══════════════════════════════════════════════

    /**
     * Create a new account.
     */
    public function createAccount(CreateAccountData $data): Account
    {
        return $this->createAccountAction->execute($data);
    }

    /**
     * Update an account.
     */
    public function updateAccount(Account $account, UpdateAccountData $data): Account
    {
        return $this->updateAccountAction->execute($account, $data);
    }

    /**
     * Delete an account.
     */
    public function deleteAccount(Account $account): void
    {
        $this->deleteAccountAction->execute($account);
    }

    /**
     * Get accounts for a company with optional filters.
     */
    public function getAccounts(Company $company, ?string $search = null, ?string $accountType = null, ?int $accountGroupId = null): \Illuminate\Database\Eloquent\Builder
    {
        return Account::query()
            ->where('company_id', $company->id)
            ->when($search, fn($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->when($accountType, fn($q, $type) => $q->where('account_type', $type))
            ->when($accountGroupId, fn($q, $id) => $q->where('account_group_id', $id))
            ->with(['accountGroup'])
            ->orderBy('code');
    }

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
                'posted_at' => now(),
            ]);
    }

    // ═══════════════════════════════════════════════
    // Auto-journal Creation (via PostingService)
    // ═══════════════════════════════════════════════

    /**
     * DR: Accounts Receivable, CR: Sales Revenue, CR: Output VAT Payable (if VAT).
     * Uses PostingService for idempotent, atomic posting with tax tagging.
     */
    public function journalForInvoice(Invoice $invoice): JournalEntry
    {
        $receivable = $this->findSystemAccount($invoice->company_id, AccountSubType::RECEIVABLE);
        $revenue = $this->findSystemAccount($invoice->company_id, AccountSubType::REVENUE);

        $lines = [
            new ProposedJournalLine(
                accountId: $receivable->id,
                debit: $invoice->total_amount,
                credit: 0,
                description: __('Accounts Receivable'),
            ),
            new ProposedJournalLine(
                accountId: $revenue->id,
                debit: 0,
                credit: $invoice->subtotal,
                description: __('Sales Revenue'),
            ),
        ];

        if ($invoice->tax_amount > 0) {
            $vatPayable = $this->findSystemAccount($invoice->company_id, AccountSubType::CURRENT_LIABILITY, '2002');
            $taxRate = $invoice->subtotal > 0
                ? (int) round(($invoice->tax_amount / $invoice->subtotal) * 10000)
                : 0;

            $lines[] = new ProposedJournalLine(
                accountId: $vatPayable->id,
                debit: 0,
                credit: $invoice->tax_amount,
                description: __('Output VAT Payable'),
                taxCode: 'OUTPUT_VAT',
                taxRate: $taxRate,
                taxBaseAmount: $invoice->subtotal,
            );
        }

        $proposed = new ProposedJournalEntry(
            companyId: $invoice->company_id,
            idempotencyKey: "invoice_{$invoice->id}_journal",
            journalCode: JournalCode::SALES,
            entryDate: ($invoice->invoice_date ?? now())->toDateString(),
            description: __('Invoice :number', ['number' => $invoice->invoice_number]),
            referenceType: 'invoice',
            referenceId: $invoice->id,
            lines: $lines,
            branchId: $invoice->branch_id ?? null,
        );

        return $this->postingService->post($proposed);
    }

    /**
     * DR: Bank/Cash, CR: Accounts Receivable.
     */
    public function journalForPayment(CustomerPayment $payment): JournalEntry
    {
        $bank = $this->findSystemAccount($payment->company_id, AccountSubType::BANK);
        $receivable = $this->findSystemAccount($payment->company_id, AccountSubType::RECEIVABLE);

        $proposed = new ProposedJournalEntry(
            companyId: $payment->company_id,
            idempotencyKey: "customer_payment_{$payment->id}_journal",
            journalCode: JournalCode::BANK,
            entryDate: ($payment->payment_date ?? now())->toDateString(),
            description: __('Payment received :receipt', ['receipt' => $payment->receipt_number]),
            referenceType: 'customer_payment',
            referenceId: $payment->id,
            lines: [
                new ProposedJournalLine(accountId: $bank->id, debit: $payment->amount, credit: 0, description: __('Bank')),
                new ProposedJournalLine(accountId: $receivable->id, debit: 0, credit: $payment->amount, description: __('Accounts Receivable')),
            ],
        );

        return $this->postingService->post($proposed);
    }

    /**
     * DR: Inventory, DR: Input VAT Receivable (if VAT), CR: Accounts Payable.
     * Tags INPUT_VAT on VAT lines for reporting.
     */
    public function journalForPurchase(GoodsReceipt $receipt): JournalEntry
    {
        $inventory = $this->findSystemAccount($receipt->company_id, AccountSubType::INVENTORY);
        $payable = $this->findSystemAccount($receipt->company_id, AccountSubType::PAYABLE);

        $receipt->loadMissing('items');
        $totalAmount = $receipt->items->sum(fn ($item) => $item->received_quantity * $item->unit_cost);
        $taxAmount = $receipt->tax_amount ?? 0;
        $netAmount = $totalAmount - $taxAmount;

        $lines = [
            new ProposedJournalLine(
                accountId: $inventory->id,
                debit: $taxAmount > 0 ? $netAmount : $totalAmount,
                credit: 0,
                description: __('Inventory'),
            ),
        ];

        // Tag INPUT_VAT for purchase-side VAT
        if ($taxAmount > 0) {
            $vatReceivable = $this->findSystemAccount($receipt->company_id, AccountSubType::CURRENT_LIABILITY, '2002');
            $taxRate = $netAmount > 0 ? (int) round(($taxAmount / $netAmount) * 10000) : 0;

            $lines[] = new ProposedJournalLine(
                accountId: $vatReceivable->id,
                debit: $taxAmount,
                credit: 0,
                description: __('Input VAT Receivable'),
                taxCode: 'INPUT_VAT',
                taxRate: $taxRate,
                taxBaseAmount: $netAmount,
            );
        }

        $lines[] = new ProposedJournalLine(
            accountId: $payable->id,
            debit: 0,
            credit: $totalAmount,
            description: __('Accounts Payable'),
        );

        $proposed = new ProposedJournalEntry(
            companyId: $receipt->company_id,
            idempotencyKey: "goods_receipt_{$receipt->id}_journal",
            journalCode: JournalCode::PURCHASE,
            entryDate: ($receipt->received_date ?? now())->toDateString(),
            description: __('GRN :number', ['number' => $receipt->grn_number]),
            referenceType: 'goods_receipt',
            referenceId: $receipt->id,
            lines: $lines,
        );

        return $this->postingService->post($proposed);
    }

    /**
     * DR: Accounts Payable, CR: Bank/Cash, CR: TDS Payable (if TDS).
     */
    public function journalForSupplierPayment(SupplierPayment $payment): JournalEntry
    {
        $payable = $this->findSystemAccount($payment->company_id, AccountSubType::PAYABLE);
        $bank = $this->findSystemAccount($payment->company_id, AccountSubType::BANK);

        $lines = [
            new ProposedJournalLine(accountId: $payable->id, debit: $payment->amount, credit: 0, description: __('Accounts Payable')),
            new ProposedJournalLine(accountId: $bank->id, debit: 0, credit: $payment->amount - ($payment->tds_amount ?? 0), description: __('Bank')),
        ];

        if (($payment->tds_amount ?? 0) > 0) {
            $tds = $this->findSystemAccount($payment->company_id, AccountSubType::CURRENT_LIABILITY, '2003');
            $lines[] = new ProposedJournalLine(accountId: $tds->id, debit: 0, credit: $payment->tds_amount, description: __('TDS Payable'));
        }

        $proposed = new ProposedJournalEntry(
            companyId: $payment->company_id,
            idempotencyKey: "supplier_payment_{$payment->id}_journal",
            journalCode: JournalCode::BANK,
            entryDate: ($payment->payment_date ?? now())->toDateString(),
            description: __('Supplier payment :number', ['number' => $payment->payment_number]),
            referenceType: 'supplier_payment',
            referenceId: $payment->id,
            lines: $lines,
        );

        return $this->postingService->post($proposed);
    }

    /**
     * DR: Salary Expense, CR: Salary Payable, CR: Tax Payable, CR: Other Deductions.
     */
    public function journalForPayroll(PayrollRun $run): JournalEntry
    {
        $salaryExpense = $this->findSystemAccount($run->company_id, AccountSubType::OPERATING_EXPENSE, '5002');
        $salaryPayable = $this->findSystemAccount($run->company_id, AccountSubType::CURRENT_LIABILITY, '2004');

        $lines = [
            new ProposedJournalLine(accountId: $salaryExpense->id, debit: $run->total_gross, credit: 0, description: __('Salary Expense')),
            new ProposedJournalLine(accountId: $salaryPayable->id, debit: 0, credit: $run->total_net, description: __('Salary Payable')),
        ];

        $creditTotal = $run->total_net;

        if ($run->total_tax > 0) {
            $taxPayable = $this->findSystemAccount($run->company_id, AccountSubType::CURRENT_LIABILITY, '2003');
            $lines[] = new ProposedJournalLine(accountId: $taxPayable->id, debit: 0, credit: $run->total_tax, description: __('Income Tax Payable'));
            $creditTotal += $run->total_tax;
        }

        $remainingDeductions = $run->total_gross - $creditTotal;
        if ($remainingDeductions > 0) {
            $lines[] = new ProposedJournalLine(accountId: $salaryPayable->id, debit: 0, credit: $remainingDeductions, description: __('Other Deductions'));
        }

        $proposed = new ProposedJournalEntry(
            companyId: $run->company_id,
            idempotencyKey: "payroll_run_{$run->id}_journal",
            journalCode: JournalCode::PAYROLL,
            entryDate: ($run->payment_date ?? now())->toDateString(),
            description: __('Payroll :number', ['number' => $run->run_number]),
            referenceType: 'payroll_run',
            referenceId: $run->id,
            lines: $lines,
        );

        return $this->postingService->post($proposed);
    }

    /**
     * DR: Expense Account, CR: Cash/Bank.
     */
    public function journalForExpense(Expense $expense): JournalEntry
    {
        $expenseAccount = $expense->account_id
            ? Account::withoutGlobalScopes()->find($expense->account_id)
            : $this->findSystemAccount($expense->company_id, AccountSubType::OPERATING_EXPENSE, '5005');

        $paymentAccount = $expense->payment_account_id
            ? Account::withoutGlobalScopes()->find($expense->payment_account_id)
            : $this->findSystemAccount($expense->company_id, AccountSubType::CASH);

        $proposed = new ProposedJournalEntry(
            companyId: $expense->company_id,
            idempotencyKey: "expense_{$expense->id}_journal",
            journalCode: JournalCode::CASH,
            entryDate: ($expense->expense_date ?? now())->toDateString(),
            description: __('Expense :number', ['number' => $expense->expense_number]),
            referenceType: 'expense',
            referenceId: $expense->id,
            lines: [
                new ProposedJournalLine(accountId: $expenseAccount->id, debit: $expense->total_amount, credit: 0, description: $expense->description),
                new ProposedJournalLine(accountId: $paymentAccount->id, debit: 0, credit: $expense->total_amount, description: __('Payment')),
            ],
        );

        return $this->postingService->post($proposed);
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

    // ═══════════════════════════════════════════════
    // Payment Method Management
    // ═══════════════════════════════════════════════

    /**
     * Get paginated payment methods for a company.
     */
    public function getPaymentMethods(int $companyId, ?string $search = null, ?bool $isActive = null, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return PaymentMethod::query()
            ->where('company_id', $companyId)
            ->when($search, fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->when($isActive !== null, fn ($q) => $q->where('is_active', $isActive))
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get a specific payment method.
     */
    public function getPaymentMethod(int $companyId, int $id): PaymentMethod
    {
        return PaymentMethod::where('company_id', $companyId)->findOrFail($id);
    }

    /**
     * Create a payment method.
     */
    public function createPaymentMethod(int $companyId, array $data): PaymentMethod
    {
        $data['company_id'] = $companyId;
        return PaymentMethod::create($data);
    }

    /**
     * Update a payment method.
     */
    public function updatePaymentMethod(PaymentMethod $paymentMethod, array $data): PaymentMethod
    {
        $paymentMethod->update($data);
        return $paymentMethod->fresh();
    }

    /**
     * Delete a payment method.
     */
    public function deletePaymentMethod(PaymentMethod $paymentMethod): void
    {
        $paymentMethod->delete();
    }

    // ═══════════════════════════════════════════════
    // Account Group Management
    // ═══════════════════════════════════════════════

    /**
     * Get paginated account groups for a company.
     */
    public function getAccountGroups(int $companyId, ?string $search = null, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return AccountGroup::query()
            ->where('company_id', $companyId)
            ->when($search, fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get a specific account group.
     */
    public function getAccountGroup(int $companyId, int $id): AccountGroup
    {
        return AccountGroup::where('company_id', $companyId)->findOrFail($id);
    }

    /**
     * Create an account group.
     */
    public function createAccountGroup(int $companyId, array $data): AccountGroup
    {
        $data['company_id'] = $companyId;
        return AccountGroup::create($data);
    }

    /**
     * Update an account group.
     */
    public function updateAccountGroup(AccountGroup $accountGroup, array $data): AccountGroup
    {
        $accountGroup->update($data);
        return $accountGroup->fresh();
    }

    /**
     * Delete an account group.
     */
    public function deleteAccountGroup(AccountGroup $accountGroup): void
    {
        $accountGroup->delete();
    }

    /**
     * Post a proposed journal entry using the PostingService.
     */
    public function postProposedEntry(ProposedJournalEntry $proposed, ?int $postedBy = null): JournalEntry
    {
        return $this->postingService->post($proposed, $postedBy);
    }

    /**
     * Reverse a posted journal entry.
     */
    public function reverseEntry(JournalEntry $original, string $idempotencyKey, string $description, ?int $reversedBy = null): JournalEntry
    {
        return $this->postingService->reverse($original, $idempotencyKey, $description, $reversedBy);
    }
}
