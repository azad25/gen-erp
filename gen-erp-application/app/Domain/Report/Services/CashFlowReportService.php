<?php

namespace App\Domain\Report\Services;

use App\Domain\Accounting\Models\JournalEntryLine;
use App\Domain\Auth\Models\Company;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Generates Cash Flow Statement using the indirect method.
 * Shows cash flows from Operating, Investing, and Financing activities.
 */
class CashFlowReportService
{
    /**
     * Generate Cash Flow Statement using indirect method.
     *
     * @return array{
     *   period: string,
     *   company: string,
     *   operating_activities: array{
     *     net_income: int,
     *     adjustments: array,
     *     working_capital_changes: array,
     *     net_cash_from_operations: int
     *   },
     *   investing_activities: array{
     *     items: array,
     *     net_cash_from_investing: int
     *   },
     *   financing_activities: array{
     *     items: array,
     *     net_cash_from_financing: int
     *   },
     *   net_change_in_cash: int,
     *   cash_beginning: int,
     *   cash_ending: int
     * }
     */
    public function generateCashFlowStatement(
        Company $company,
        Carbon $fromDate,
        Carbon $toDate
    ): array {
        // Get net income for the period
        $netIncome = $this->getNetIncome($company, $fromDate, $toDate);

        // Get operating activities
        $operatingActivities = $this->getOperatingActivities($company, $fromDate, $toDate, $netIncome);

        // Get investing activities
        $investingActivities = $this->getInvestingActivities($company, $fromDate, $toDate);

        // Get financing activities
        $financingActivities = $this->getFinancingActivities($company, $fromDate, $toDate);

        // Calculate net change in cash
        $netChangeInCash = $operatingActivities['net_cash_from_operations'] +
                          $investingActivities['net_cash_from_investing'] +
                          $financingActivities['net_cash_from_financing'];

        // Get beginning and ending cash balances
        $cashBeginning = $this->getCashBalance($company, $fromDate->copy()->subDay());
        $cashEnding = $this->getCashBalance($company, $toDate);

        return [
            'period' => $fromDate->format('d M Y') . ' - ' . $toDate->format('d M Y'),
            'company' => $company->name,
            'operating_activities' => $operatingActivities,
            'investing_activities' => $investingActivities,
            'financing_activities' => $financingActivities,
            'net_change_in_cash' => $netChangeInCash,
            'cash_beginning' => $cashBeginning,
            'cash_ending' => $cashEnding,
            'reconciliation_difference' => $cashEnding - ($cashBeginning + $netChangeInCash),
        ];
    }

    /**
     * Get net income for the period.
     */
    private function getNetIncome(Company $company, Carbon $fromDate, Carbon $toDate): int
    {
        // Revenue accounts (4000-4999)
        $revenue = JournalEntryLine::query()
            ->whereHas('journalEntry', function ($q) use ($company, $fromDate, $toDate) {
                $q->where('company_id', $company->id)
                    ->where('status', 'posted')
                    ->whereBetween('entry_date', [$fromDate, $toDate]);
            })
            ->whereHas('account', function ($q) {
                $q->whereBetween('account_code', ['4000', '4999']);
            })
            ->sum(\DB::raw('credit - debit'));

        // Expense accounts (5000-6999)
        $expenses = JournalEntryLine::query()
            ->whereHas('journalEntry', function ($q) use ($company, $fromDate, $toDate) {
                $q->where('company_id', $company->id)
                    ->where('status', 'posted')
                    ->whereBetween('entry_date', [$fromDate, $toDate]);
            })
            ->whereHas('account', function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereBetween('account_code', ['5000', '5999'])
                        ->orWhereBetween('account_code', ['6000', '6999']);
                });
            })
            ->sum(\DB::raw('debit - credit'));

        return $revenue - $expenses;
    }

    /**
     * Get operating activities using indirect method.
     */
    private function getOperatingActivities(Company $company, Carbon $fromDate, Carbon $toDate, int $netIncome): array
    {
        // Non-cash adjustments
        $adjustments = [
            'depreciation' => $this->getAccountMovement($company, $fromDate, $toDate, ['6200', '6299'], 'debit'), // Depreciation expense
            'amortization' => $this->getAccountMovement($company, $fromDate, $toDate, ['6300', '6399'], 'debit'), // Amortization expense
            'bad_debt_expense' => $this->getAccountMovement($company, $fromDate, $toDate, ['6400', '6499'], 'debit'), // Bad debt expense
        ];

        // Working capital changes (increase in current assets/decrease in current liabilities = cash outflow)
        $workingCapitalChanges = [
            'accounts_receivable' => -$this->getBalanceChange($company, $fromDate, $toDate, ['1200', '1299']), // AR increase = cash outflow
            'inventory' => -$this->getBalanceChange($company, $fromDate, $toDate, ['1300', '1399']), // Inventory increase = cash outflow
            'prepaid_expenses' => -$this->getBalanceChange($company, $fromDate, $toDate, ['1400', '1499']), // Prepaid increase = cash outflow
            'accounts_payable' => $this->getBalanceChange($company, $fromDate, $toDate, ['2100', '2199']), // AP increase = cash inflow
            'accrued_liabilities' => $this->getBalanceChange($company, $fromDate, $toDate, ['2200', '2299']), // Accrued increase = cash inflow
            'deferred_revenue' => $this->getBalanceChange($company, $fromDate, $toDate, ['2300', '2399']), // Deferred revenue increase = cash inflow
        ];

        $totalAdjustments = array_sum($adjustments);
        $totalWorkingCapitalChanges = array_sum($workingCapitalChanges);
        $netCashFromOperations = $netIncome + $totalAdjustments + $totalWorkingCapitalChanges;

        return [
            'net_income' => $netIncome,
            'adjustments' => [
                'items' => $adjustments,
                'total' => $totalAdjustments,
            ],
            'working_capital_changes' => [
                'items' => $workingCapitalChanges,
                'total' => $totalWorkingCapitalChanges,
            ],
            'net_cash_from_operations' => $netCashFromOperations,
        ];
    }

    /**
     * Get investing activities.
     */
    private function getInvestingActivities(Company $company, Carbon $fromDate, Carbon $toDate): array
    {
        $items = [
            'property_plant_equipment' => -$this->getBalanceChange($company, $fromDate, $toDate, ['1500', '1599']), // PPE increase = cash outflow
            'intangible_assets' => -$this->getBalanceChange($company, $fromDate, $toDate, ['1600', '1699']), // Intangible increase = cash outflow
            'investments' => -$this->getBalanceChange($company, $fromDate, $toDate, ['1700', '1799']), // Investment increase = cash outflow
            'asset_disposals' => $this->getAssetDisposals($company, $fromDate, $toDate), // Asset sales = cash inflow
        ];

        $netCashFromInvesting = array_sum($items);

        return [
            'items' => $items,
            'net_cash_from_investing' => $netCashFromInvesting,
        ];
    }

    /**
     * Get financing activities.
     */
    private function getFinancingActivities(Company $company, Carbon $fromDate, Carbon $toDate): array
    {
        $items = [
            'long_term_debt' => $this->getBalanceChange($company, $fromDate, $toDate, ['2500', '2599']), // Debt increase = cash inflow
            'share_capital' => $this->getBalanceChange($company, $fromDate, $toDate, ['3100', '3199']), // Capital increase = cash inflow
            'retained_earnings' => $this->getRetainedEarningsChange($company, $fromDate, $toDate), // Dividends = cash outflow
            'short_term_borrowings' => $this->getBalanceChange($company, $fromDate, $toDate, ['2400', '2499']), // Short-term debt
        ];

        $netCashFromFinancing = array_sum($items);

        return [
            'items' => $items,
            'net_cash_from_financing' => $netCashFromFinancing,
        ];
    }

    /**
     * Get account movement for a period (debits or credits).
     */
    private function getAccountMovement(Company $company, Carbon $fromDate, Carbon $toDate, array $accountCodeRange, string $type): int
    {
        $query = JournalEntryLine::query()
            ->whereHas('journalEntry', function ($q) use ($company, $fromDate, $toDate) {
                $q->where('company_id', $company->id)
                    ->where('status', 'posted')
                    ->whereBetween('entry_date', [$fromDate, $toDate]);
            })
            ->whereHas('account', function ($q) use ($accountCodeRange) {
                $q->whereBetween('account_code', [$accountCodeRange[0], $accountCodeRange[1]]);
            });

        return $type === 'debit' 
            ? $query->sum('debit')
            : $query->sum('credit');
    }

    /**
     * Get balance change for account range between periods.
     */
    private function getBalanceChange(Company $company, Carbon $fromDate, Carbon $toDate, array $accountCodeRange): int
    {
        $endingBalance = $this->getAccountRangeBalance($company, $toDate, $accountCodeRange);
        $beginningBalance = $this->getAccountRangeBalance($company, $fromDate->copy()->subDay(), $accountCodeRange);

        return $endingBalance - $beginningBalance;
    }

    /**
     * Get balance for account range as of a date.
     */
    private function getAccountRangeBalance(Company $company, Carbon $asOfDate, array $accountCodeRange): int
    {
        return JournalEntryLine::query()
            ->whereHas('journalEntry', function ($q) use ($company, $asOfDate) {
                $q->where('company_id', $company->id)
                    ->where('status', 'posted')
                    ->where('entry_date', '<=', $asOfDate);
            })
            ->whereHas('account', function ($q) use ($accountCodeRange) {
                $q->whereBetween('account_code', [$accountCodeRange[0], $accountCodeRange[1]]);
            })
            ->sum(\DB::raw('debit - credit'));
    }

    /**
     * Get cash balance as of a date.
     */
    private function getCashBalance(Company $company, Carbon $asOfDate): int
    {
        // Cash accounts (1000-1099)
        return $this->getAccountRangeBalance($company, $asOfDate, ['1000', '1099']);
    }

    /**
     * Get asset disposals (simplified - would need more complex logic in practice).
     */
    private function getAssetDisposals(Company $company, Carbon $fromDate, Carbon $toDate): int
    {
        // This is simplified - in practice, you'd track asset disposals separately
        // For now, we'll look for credits to fixed asset accounts (which might indicate disposals)
        return $this->getAccountMovement($company, $fromDate, $toDate, ['1500', '1599'], 'credit');
    }

    /**
     * Get retained earnings change (dividends paid).
     */
    private function getRetainedEarningsChange(Company $company, Carbon $fromDate, Carbon $toDate): int
    {
        // Look for dividend payments (debits to retained earnings)
        $dividendPayments = $this->getAccountMovement($company, $fromDate, $toDate, ['3200', '3299'], 'debit');
        
        // Dividend payments are cash outflows, so return negative
        return -$dividendPayments;
    }

    /**
     * Generate simplified cash flow statement (direct method).
     */
    public function generateDirectCashFlowStatement(Company $company, Carbon $fromDate, Carbon $toDate): array
    {
        // Operating activities - direct method
        $operatingActivities = [
            'cash_from_customers' => $this->getCashFromCustomers($company, $fromDate, $toDate),
            'cash_to_suppliers' => $this->getCashToSuppliers($company, $fromDate, $toDate),
            'cash_for_operating_expenses' => $this->getCashForOperatingExpenses($company, $fromDate, $toDate),
            'cash_for_interest' => $this->getCashForInterest($company, $fromDate, $toDate),
            'cash_for_taxes' => $this->getCashForTaxes($company, $fromDate, $toDate),
        ];

        $netCashFromOperations = array_sum($operatingActivities);

        // Investing and financing activities remain the same
        $investingActivities = $this->getInvestingActivities($company, $fromDate, $toDate);
        $financingActivities = $this->getFinancingActivities($company, $fromDate, $toDate);

        $netChangeInCash = $netCashFromOperations +
                          $investingActivities['net_cash_from_investing'] +
                          $financingActivities['net_cash_from_financing'];

        $cashBeginning = $this->getCashBalance($company, $fromDate->copy()->subDay());
        $cashEnding = $this->getCashBalance($company, $toDate);

        return [
            'method' => 'Direct',
            'period' => $fromDate->format('d M Y') . ' - ' . $toDate->format('d M Y'),
            'company' => $company->name,
            'operating_activities' => [
                'items' => $operatingActivities,
                'net_cash_from_operations' => $netCashFromOperations,
            ],
            'investing_activities' => $investingActivities,
            'financing_activities' => $financingActivities,
            'net_change_in_cash' => $netChangeInCash,
            'cash_beginning' => $cashBeginning,
            'cash_ending' => $cashEnding,
            'reconciliation_difference' => $cashEnding - ($cashBeginning + $netChangeInCash),
        ];
    }

    /**
     * Get cash received from customers (direct method).
     */
    private function getCashFromCustomers(Company $company, Carbon $fromDate, Carbon $toDate): int
    {
        // Cash received = Sales + Beginning AR - Ending AR
        $sales = $this->getAccountMovement($company, $fromDate, $toDate, ['4000', '4999'], 'credit');
        $arChange = $this->getBalanceChange($company, $fromDate, $toDate, ['1200', '1299']);
        
        return $sales - $arChange;
    }

    /**
     * Get cash paid to suppliers (direct method).
     */
    private function getCashToSuppliers(Company $company, Carbon $fromDate, Carbon $toDate): int
    {
        // Cash paid = COGS + Beginning AP - Ending AP
        $cogs = $this->getAccountMovement($company, $fromDate, $toDate, ['5000', '5999'], 'debit');
        $apChange = $this->getBalanceChange($company, $fromDate, $toDate, ['2100', '2199']);
        
        return -($cogs - $apChange); // Negative because it's cash outflow
    }

    /**
     * Get cash paid for operating expenses (direct method).
     */
    private function getCashForOperatingExpenses(Company $company, Carbon $fromDate, Carbon $toDate): int
    {
        $operatingExpenses = $this->getAccountMovement($company, $fromDate, $toDate, ['6000', '6999'], 'debit');
        
        return -$operatingExpenses; // Negative because it's cash outflow
    }

    /**
     * Get cash paid for interest (direct method).
     */
    private function getCashForInterest(Company $company, Carbon $fromDate, Carbon $toDate): int
    {
        $interestExpense = $this->getAccountMovement($company, $fromDate, $toDate, ['7100', '7199'], 'debit');
        
        return -$interestExpense; // Negative because it's cash outflow
    }

    /**
     * Get cash paid for taxes (direct method).
     */
    private function getCashForTaxes(Company $company, Carbon $fromDate, Carbon $toDate): int
    {
        $taxExpense = $this->getAccountMovement($company, $fromDate, $toDate, ['7200', '7299'], 'debit');
        
        return -$taxExpense; // Negative because it's cash outflow
    }
}