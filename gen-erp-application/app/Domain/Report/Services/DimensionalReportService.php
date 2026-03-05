<?php

namespace App\Domain\Report\Services;

use App\Domain\Accounting\Models\JournalEntryLine;
use App\Domain\Auth\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Generates dimensional financial reports (P&L, Balance Sheet) with branch, cost center, and custom dimension filtering.
 */
class DimensionalReportService
{
    /**
     * Generate Profit & Loss report with dimensional filtering.
     *
     * @param array $dimensions Filter by dimensions: ['branch_id' => 1, 'cost_center_id' => 2, 'custom' => ['project_id' => 'PRJ-001']]
     * @return array{
     *   period: string,
     *   company: string,
     *   dimensions: array,
     *   revenue: array{total: int, accounts: array},
     *   expenses: array{total: int, accounts: array},
     *   net_income: int,
     *   line_count: int
     * }
     */
    public function dimensionalProfitAndLoss(
        Company $company,
        Carbon $fromDate,
        Carbon $toDate,
        array $dimensions = []
    ): array {
        $query = JournalEntryLine::query()
            ->with(['journalEntry', 'account'])
            ->whereHas('journalEntry', function ($q) use ($company, $fromDate, $toDate) {
                $q->where('company_id', $company->id)
                    ->where('status', 'posted')
                    ->whereBetween('entry_date', [$fromDate->toDateString(), $toDate->toDateString()]);
            })
            ->whereHas('account', function ($q) {
                // Revenue accounts (4000-4999) and Expense accounts (5000-5999, 6000-6999)
                $q->where(function ($subQ) {
                    $subQ->whereBetween('code', ['4000', '4999'])  // Revenue
                        ->orWhereBetween('code', ['5000', '5999'])  // COGS
                        ->orWhereBetween('code', ['6000', '6999']); // Operating Expenses
                });
            });

        // Apply dimensional filters
        if (isset($dimensions['branch_id'])) {
            $query->where('branch_id', $dimensions['branch_id']);
        }

        if (isset($dimensions['cost_center_id'])) {
            $query->where('cost_center_id', $dimensions['cost_center_id']);
        }

        // Apply custom dimension filters
        if (isset($dimensions['custom']) && is_array($dimensions['custom'])) {
            foreach ($dimensions['custom'] as $key => $value) {
                $query->whereJsonContains("dimensions->{$key}", $value);
            }
        }

        $lines = $query->get();

        // Categorize accounts
        $revenueLines = $lines->filter(function ($line) {
            $code = $line->account->code ?? '';
            return $code >= '4000' && $code <= '4999';
        });

        $expenseLines = $lines->filter(function ($line) {
            $code = $line->account->code ?? '';
            return ($code >= '5000' && $code <= '5999') || ($code >= '6000' && $code <= '6999');
        });

        // Calculate revenue (credits - debits for revenue accounts)
        $revenueAccounts = $this->groupByAccount($revenueLines, 'revenue');
        $totalRevenue = $revenueAccounts->sum('net_amount');

        // Calculate expenses (debits - credits for expense accounts)
        $expenseAccounts = $this->groupByAccount($expenseLines, 'expense');
        $totalExpenses = $expenseAccounts->sum('net_amount');

        return [
            'period' => $fromDate->format('d M Y') . ' - ' . $toDate->format('d M Y'),
            'company' => $company->name,
            'dimensions' => $dimensions,
            'revenue' => [
                'total' => $totalRevenue,
                'accounts' => $revenueAccounts->values()->toArray(),
            ],
            'expenses' => [
                'total' => $totalExpenses,
                'accounts' => $expenseAccounts->values()->toArray(),
            ],
            'net_income' => $totalRevenue - $totalExpenses,
            'line_count' => $lines->count(),
        ];
    }

    /**
     * Generate Balance Sheet with dimensional filtering.
     */
    public function dimensionalBalanceSheet(
        Company $company,
        Carbon $asOfDate,
        array $dimensions = []
    ): array {
        $query = JournalEntryLine::query()
            ->with(['journalEntry', 'account'])
            ->whereHas('journalEntry', function ($q) use ($company, $asOfDate) {
                $q->where('company_id', $company->id)
                    ->where('status', 'posted')
                    ->where('entry_date', '<=', $asOfDate->toDateString());
            })
            ->whereHas('account', function ($q) {
                // Assets (1000-1999), Liabilities (2000-2999), Equity (3000-3999)
                $q->where(function ($subQ) {
                    $subQ->whereBetween('code', ['1000', '1999'])  // Assets
                        ->orWhereBetween('code', ['2000', '2999'])  // Liabilities
                        ->orWhereBetween('code', ['3000', '3999']); // Equity
                });
            });

        // Apply dimensional filters (same as P&L)
        if (isset($dimensions['branch_id'])) {
            $query->where('branch_id', $dimensions['branch_id']);
        }

        if (isset($dimensions['cost_center_id'])) {
            $query->where('cost_center_id', $dimensions['cost_center_id']);
        }

        if (isset($dimensions['custom']) && is_array($dimensions['custom'])) {
            foreach ($dimensions['custom'] as $key => $value) {
                $query->whereJsonContains("dimensions->{$key}", $value);
            }
        }

        $lines = $query->get();

        // Categorize accounts
        $assetLines = $lines->filter(function ($line) {
            $code = $line->account->code ?? '';
            return $code >= '1000' && $code <= '1999';
        });

        $liabilityLines = $lines->filter(function ($line) {
            $code = $line->account->code ?? '';
            return $code >= '2000' && $code <= '2999';
        });

        $equityLines = $lines->filter(function ($line) {
            $code = $line->account->code ?? '';
            return $code >= '3000' && $code <= '3999';
        });

        // Calculate balances (debits - credits for assets, credits - debits for liabilities/equity)
        $assetAccounts = $this->groupByAccount($assetLines, 'asset');
        $liabilityAccounts = $this->groupByAccount($liabilityLines, 'liability');
        $equityAccounts = $this->groupByAccount($equityLines, 'equity');

        $totalAssets = $assetAccounts->sum('net_amount');
        $totalLiabilities = $liabilityAccounts->sum('net_amount');
        $totalEquity = $equityAccounts->sum('net_amount');

        return [
            'as_of_date' => $asOfDate->format('d M Y'),
            'company' => $company->name,
            'dimensions' => $dimensions,
            'assets' => [
                'total' => $totalAssets,
                'accounts' => $assetAccounts->values()->toArray(),
            ],
            'liabilities' => [
                'total' => $totalLiabilities,
                'accounts' => $liabilityAccounts->values()->toArray(),
            ],
            'equity' => [
                'total' => $totalEquity,
                'accounts' => $equityAccounts->values()->toArray(),
            ],
            'total_liabilities_and_equity' => $totalLiabilities + $totalEquity,
            'balance_check' => $totalAssets - ($totalLiabilities + $totalEquity), // Should be 0
        ];
    }

    /**
     * Group journal lines by account and calculate net amounts.
     */
    private function groupByAccount(Collection $lines, string $accountType): Collection
    {
        return $lines->groupBy('account_id')->map(function ($accountLines, $accountId) use ($accountType) {
            $account = $accountLines->first()->account;
            $totalDebits = $accountLines->sum('debit');
            $totalCredits = $accountLines->sum('credit');

            // Calculate net amount based on account type
            $netAmount = match ($accountType) {
                'revenue' => $totalCredits - $totalDebits,  // Revenue: credits are positive
                'expense' => $totalDebits - $totalCredits,  // Expenses: debits are positive
                'asset' => $totalDebits - $totalCredits,    // Assets: debits are positive
                'liability', 'equity' => $totalCredits - $totalDebits, // Liabilities/Equity: credits are positive
                default => $totalDebits - $totalCredits,
            };

            return [
                'account_id' => $accountId,
                'account_code' => $account->code ?? '',
                'account_name' => $account->name ?? '',
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
                'net_amount' => $netAmount,
                'line_count' => $accountLines->count(),
            ];
        });
    }

    /**
     * Get available dimension values for filtering.
     */
    public function getAvailableDimensions(Company $company): array
    {
        // Get distinct branch_ids
        $branches = JournalEntryLine::whereHas('journalEntry', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })
            ->whereNotNull('branch_id')
            ->with('branch')
            ->distinct('branch_id')
            ->get(['branch_id'])
            ->pluck('branch.name', 'branch_id')
            ->toArray();

        // Get distinct cost_center_ids
        $costCenters = JournalEntryLine::whereHas('journalEntry', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })
            ->whereNotNull('cost_center_id')
            ->with('costCenter')
            ->distinct('cost_center_id')
            ->get(['cost_center_id'])
            ->pluck('costCenter.name', 'cost_center_id')
            ->toArray();

        // Get sample custom dimensions (this would need more sophisticated analysis in production)
        $customDimensions = JournalEntryLine::whereHas('journalEntry', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })
            ->whereNotNull('dimensions')
            ->limit(100)
            ->get(['dimensions'])
            ->pluck('dimensions')
            ->filter()
            ->flatMap(function ($dims) {
                return array_keys($dims);
            })
            ->unique()
            ->values()
            ->toArray();

        return [
            'branches' => $branches,
            'cost_centers' => $costCenters,
            'custom_dimension_keys' => $customDimensions,
        ];
    }
}