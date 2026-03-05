<?php

namespace App\Domain\Report\Services;

use App\Domain\Auth\Models\Company;
use App\Domain\Report\Services\DimensionalReportService;
use Carbon\Carbon;

/**
 * Generates comparative financial reports (Year-over-Year, Period-over-Period).
 */
class ComparativeReportService
{
    public function __construct(
        private readonly DimensionalReportService $dimensionalReportService,
    ) {}

    /**
     * Generate Year-over-Year Profit & Loss comparison.
     *
     * @return array{
     *   comparison_type: string,
     *   current_period: array,
     *   previous_period: array,
     *   variance: array{
     *     revenue: array{amount: int, percentage: float},
     *     expenses: array{amount: int, percentage: float},
     *     net_income: array{amount: int, percentage: float}
     *   },
     *   accounts: array
     * }
     */
    public function yearOverYearProfitAndLoss(
        Company $company,
        Carbon $currentFromDate,
        Carbon $currentToDate,
        array $dimensions = []
    ): array {
        // Calculate previous year dates
        $previousFromDate = $currentFromDate->copy()->subYear();
        $previousToDate = $currentToDate->copy()->subYear();

        // Get current period P&L
        $currentPL = $this->dimensionalReportService->dimensionalProfitAndLoss(
            $company,
            $currentFromDate,
            $currentToDate,
            $dimensions
        );

        // Get previous period P&L
        $previousPL = $this->dimensionalReportService->dimensionalProfitAndLoss(
            $company,
            $previousFromDate,
            $previousToDate,
            $dimensions
        );

        // Calculate variances
        $revenueVariance = $this->calculateVariance(
            $currentPL['revenue']['total'],
            $previousPL['revenue']['total']
        );

        $expenseVariance = $this->calculateVariance(
            $currentPL['expenses']['total'],
            $previousPL['expenses']['total']
        );

        $netIncomeVariance = $this->calculateVariance(
            $currentPL['net_income'],
            $previousPL['net_income']
        );

        // Create account-level comparison
        $accountComparison = $this->compareAccountDetails(
            $currentPL['revenue']['accounts'] ?? [],
            $previousPL['revenue']['accounts'] ?? [],
            'revenue'
        );

        $expenseComparison = $this->compareAccountDetails(
            $currentPL['expenses']['accounts'] ?? [],
            $previousPL['expenses']['accounts'] ?? [],
            'expenses'
        );

        return [
            'comparison_type' => 'Year-over-Year',
            'current_period' => [
                'period' => $currentPL['period'],
                'revenue' => $currentPL['revenue']['total'],
                'expenses' => $currentPL['expenses']['total'],
                'net_income' => $currentPL['net_income'],
            ],
            'previous_period' => [
                'period' => $previousPL['period'],
                'revenue' => $previousPL['revenue']['total'],
                'expenses' => $previousPL['expenses']['total'],
                'net_income' => $previousPL['net_income'],
            ],
            'variance' => [
                'revenue' => $revenueVariance,
                'expenses' => $expenseVariance,
                'net_income' => $netIncomeVariance,
            ],
            'accounts' => [
                'revenue' => $accountComparison,
                'expenses' => $expenseComparison,
            ],
        ];
    }

    /**
     * Generate Month-over-Month comparison.
     */
    public function monthOverMonthProfitAndLoss(
        Company $company,
        Carbon $currentFromDate,
        Carbon $currentToDate,
        array $dimensions = []
    ): array {
        // Calculate previous month dates
        $previousFromDate = $currentFromDate->copy()->subMonth();
        $previousToDate = $currentToDate->copy()->subMonth();

        return $this->generateComparison(
            $company,
            $currentFromDate,
            $currentToDate,
            $previousFromDate,
            $previousToDate,
            'Month-over-Month',
            $dimensions
        );
    }

    /**
     * Generate Quarter-over-Quarter comparison.
     */
    public function quarterOverQuarterProfitAndLoss(
        Company $company,
        Carbon $currentFromDate,
        Carbon $currentToDate,
        array $dimensions = []
    ): array {
        // Calculate previous quarter dates
        $previousFromDate = $currentFromDate->copy()->subQuarter();
        $previousToDate = $currentToDate->copy()->subQuarter();

        return $this->generateComparison(
            $company,
            $currentFromDate,
            $currentToDate,
            $previousFromDate,
            $previousToDate,
            'Quarter-over-Quarter',
            $dimensions
        );
    }

    /**
     * Generate Balance Sheet comparison.
     */
    public function yearOverYearBalanceSheet(
        Company $company,
        Carbon $currentDate,
        array $dimensions = []
    ): array {
        $previousDate = $currentDate->copy()->subYear();

        // Get current balance sheet
        $currentBS = $this->dimensionalReportService->dimensionalBalanceSheet(
            $company,
            $currentDate,
            $dimensions
        );

        // Get previous balance sheet
        $previousBS = $this->dimensionalReportService->dimensionalBalanceSheet(
            $company,
            $previousDate,
            $dimensions
        );

        // Calculate variances
        $assetVariance = $this->calculateVariance(
            $currentBS['assets']['total'],
            $previousBS['assets']['total']
        );

        $liabilityVariance = $this->calculateVariance(
            $currentBS['liabilities']['total'],
            $previousBS['liabilities']['total']
        );

        $equityVariance = $this->calculateVariance(
            $currentBS['equity']['total'],
            $previousBS['equity']['total']
        );

        return [
            'comparison_type' => 'Year-over-Year Balance Sheet',
            'current_period' => [
                'as_of_date' => $currentBS['as_of_date'],
                'assets' => $currentBS['assets']['total'],
                'liabilities' => $currentBS['liabilities']['total'],
                'equity' => $currentBS['equity']['total'],
            ],
            'previous_period' => [
                'as_of_date' => $previousBS['as_of_date'],
                'assets' => $previousBS['assets']['total'],
                'liabilities' => $previousBS['liabilities']['total'],
                'equity' => $previousBS['equity']['total'],
            ],
            'variance' => [
                'assets' => $assetVariance,
                'liabilities' => $liabilityVariance,
                'equity' => $equityVariance,
            ],
            'accounts' => [
                'assets' => $this->compareAccountDetails(
                    $currentBS['assets']['accounts'] ?? [],
                    $previousBS['assets']['accounts'] ?? [],
                    'assets'
                ),
                'liabilities' => $this->compareAccountDetails(
                    $currentBS['liabilities']['accounts'] ?? [],
                    $previousBS['liabilities']['accounts'] ?? [],
                    'liabilities'
                ),
                'equity' => $this->compareAccountDetails(
                    $currentBS['equity']['accounts'] ?? [],
                    $previousBS['equity']['accounts'] ?? [],
                    'equity'
                ),
            ],
        ];
    }

    /**
     * Generate generic period comparison.
     */
    private function generateComparison(
        Company $company,
        Carbon $currentFromDate,
        Carbon $currentToDate,
        Carbon $previousFromDate,
        Carbon $previousToDate,
        string $comparisonType,
        array $dimensions = []
    ): array {
        // Get current period P&L
        $currentPL = $this->dimensionalReportService->dimensionalProfitAndLoss(
            $company,
            $currentFromDate,
            $currentToDate,
            $dimensions
        );

        // Get previous period P&L
        $previousPL = $this->dimensionalReportService->dimensionalProfitAndLoss(
            $company,
            $previousFromDate,
            $previousToDate,
            $dimensions
        );

        // Calculate variances
        $revenueVariance = $this->calculateVariance(
            $currentPL['revenue']['total'],
            $previousPL['revenue']['total']
        );

        $expenseVariance = $this->calculateVariance(
            $currentPL['expenses']['total'],
            $previousPL['expenses']['total']
        );

        $netIncomeVariance = $this->calculateVariance(
            $currentPL['net_income'],
            $previousPL['net_income']
        );

        return [
            'comparison_type' => $comparisonType,
            'current_period' => [
                'period' => $currentPL['period'],
                'revenue' => $currentPL['revenue']['total'],
                'expenses' => $currentPL['expenses']['total'],
                'net_income' => $currentPL['net_income'],
            ],
            'previous_period' => [
                'period' => $previousPL['period'],
                'revenue' => $previousPL['revenue']['total'],
                'expenses' => $previousPL['expenses']['total'],
                'net_income' => $previousPL['net_income'],
            ],
            'variance' => [
                'revenue' => $revenueVariance,
                'expenses' => $expenseVariance,
                'net_income' => $netIncomeVariance,
            ],
        ];
    }

    /**
     * Calculate variance between current and previous amounts.
     */
    private function calculateVariance(int $current, int $previous): array
    {
        $amount = $current - $previous;
        $percentage = $previous != 0 ? (($current - $previous) / abs($previous)) * 100 : 0;

        return [
            'amount' => $amount,
            'percentage' => round($percentage, 2),
            'direction' => $amount > 0 ? 'increase' : ($amount < 0 ? 'decrease' : 'no_change'),
            'is_favorable' => $this->isFavorableVariance($amount, 'revenue'), // Default to revenue logic
        ];
    }

    /**
     * Determine if variance is favorable based on account type.
     */
    private function isFavorableVariance(int $amount, string $accountType): bool
    {
        return match ($accountType) {
            'revenue', 'assets', 'equity' => $amount > 0, // Increase is favorable
            'expenses', 'liabilities' => $amount < 0,     // Decrease is favorable
            default => $amount > 0,
        };
    }

    /**
     * Compare account details between periods.
     */
    private function compareAccountDetails(array $currentAccounts, array $previousAccounts, string $accountType): array
    {
        $comparison = [];
        
        // Create lookup for previous accounts
        $previousLookup = collect($previousAccounts)->keyBy('account_id');
        
        foreach ($currentAccounts as $currentAccount) {
            $accountId = $currentAccount['account_id'];
            $previousAccount = $previousLookup->get($accountId);
            
            $previousAmount = $previousAccount['net_amount'] ?? 0;
            $variance = $this->calculateVariance($currentAccount['net_amount'], $previousAmount);
            $variance['is_favorable'] = $this->isFavorableVariance($variance['amount'], $accountType);
            
            $comparison[] = [
                'account_id' => $accountId,
                'account_code' => $currentAccount['account_code'],
                'account_name' => $currentAccount['account_name'],
                'current_amount' => $currentAccount['net_amount'],
                'previous_amount' => $previousAmount,
                'variance' => $variance,
            ];
        }
        
        // Sort by absolute variance amount (largest changes first)
        usort($comparison, function ($a, $b) {
            return abs($b['variance']['amount']) <=> abs($a['variance']['amount']);
        });
        
        return $comparison;
    }

    /**
     * Generate trend analysis over multiple periods.
     */
    public function trendAnalysis(
        Company $company,
        Carbon $endDate,
        int $periods = 12,
        string $periodType = 'month',
        array $dimensions = []
    ): array {
        $trends = [];
        $currentDate = $endDate->copy();

        for ($i = 0; $i < $periods; $i++) {
            $fromDate = match ($periodType) {
                'month' => $currentDate->copy()->startOfMonth(),
                'quarter' => $currentDate->copy()->startOfQuarter(),
                'year' => $currentDate->copy()->startOfYear(),
                default => $currentDate->copy()->startOfMonth(),
            };

            $toDate = match ($periodType) {
                'month' => $currentDate->copy()->endOfMonth(),
                'quarter' => $currentDate->copy()->endOfQuarter(),
                'year' => $currentDate->copy()->endOfYear(),
                default => $currentDate->copy()->endOfMonth(),
            };

            $pl = $this->dimensionalReportService->dimensionalProfitAndLoss(
                $company,
                $fromDate,
                $toDate,
                $dimensions
            );

            $trends[] = [
                'period' => $fromDate->format('M Y'),
                'period_start' => $fromDate->toDateString(),
                'period_end' => $toDate->toDateString(),
                'revenue' => $pl['revenue']['total'],
                'expenses' => $pl['expenses']['total'],
                'net_income' => $pl['net_income'],
                'gross_margin' => $pl['revenue']['total'] > 0 
                    ? round((($pl['revenue']['total'] - $pl['expenses']['total']) / $pl['revenue']['total']) * 100, 2)
                    : 0,
            ];

            // Move to previous period
            $currentDate = match ($periodType) {
                'month' => $currentDate->subMonth(),
                'quarter' => $currentDate->subQuarter(),
                'year' => $currentDate->subYear(),
                default => $currentDate->subMonth(),
            };
        }

        // Reverse to show oldest to newest
        $trends = array_reverse($trends);

        return [
            'period_type' => $periodType,
            'periods_analyzed' => $periods,
            'company' => $company->name,
            'dimensions' => $dimensions,
            'trends' => $trends,
            'summary' => $this->calculateTrendSummary($trends),
        ];
    }

    /**
     * Calculate trend summary statistics.
     */
    private function calculateTrendSummary(array $trends): array
    {
        if (empty($trends)) {
            return [];
        }

        $revenues = array_column($trends, 'revenue');
        $expenses = array_column($trends, 'expenses');
        $netIncomes = array_column($trends, 'net_income');

        return [
            'revenue' => [
                'average' => round(array_sum($revenues) / count($revenues)),
                'min' => min($revenues),
                'max' => max($revenues),
                'growth_rate' => $this->calculateGrowthRate($revenues),
            ],
            'expenses' => [
                'average' => round(array_sum($expenses) / count($expenses)),
                'min' => min($expenses),
                'max' => max($expenses),
                'growth_rate' => $this->calculateGrowthRate($expenses),
            ],
            'net_income' => [
                'average' => round(array_sum($netIncomes) / count($netIncomes)),
                'min' => min($netIncomes),
                'max' => max($netIncomes),
                'growth_rate' => $this->calculateGrowthRate($netIncomes),
            ],
        ];
    }

    /**
     * Calculate compound annual growth rate.
     */
    private function calculateGrowthRate(array $values): float
    {
        if (count($values) < 2 || $values[0] == 0) {
            return 0;
        }

        $firstValue = abs($values[0]);
        $lastValue = abs($values[count($values) - 1]);
        $periods = count($values) - 1;

        if ($firstValue == 0) {
            return 0;
        }

        $growthRate = (pow($lastValue / $firstValue, 1 / $periods) - 1) * 100;
        
        return round($growthRate, 2);
    }
}