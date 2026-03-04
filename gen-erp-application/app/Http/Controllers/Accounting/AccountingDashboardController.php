<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AccountingDashboardController extends Controller
{
    /**
     * Display the accounting dashboard.
     */
    public function index(Request $request): Response
    {
        $company = CompanyContext::active();
        $period = $request->get('period', '30d');
        
        // Calculate date range based on period
        $endDate = Carbon::now();
        $startDate = match($period) {
            '7d' => $endDate->copy()->subDays(7),
            '30d' => $endDate->copy()->subDays(30),
            '90d' => $endDate->copy()->subDays(90),
            default => $endDate->copy()->subDays(30)
        };

        return Inertia::render('Accounting/Dashboard', [
            'metrics' => $this->getAccountingMetrics($company, $startDate, $endDate),
            'chartData' => $this->getChartData($company, $startDate, $endDate),
            'accountBalances' => $this->getAccountBalances($company),
            'financialRatios' => $this->getFinancialRatios($company),
            'recentTransactions' => $this->getRecentTransactions($company),
            'expenseCategories' => $this->getExpenseCategories($company, $startDate, $endDate),
            'taxSummary' => $this->getTaxSummary($company),
        ]);
    }

    /**
     * Get accounting metrics for the dashboard.
     */
    private function getAccountingMetrics($company, $startDate, $endDate): array
    {
        // Revenue calculation
        $currentRevenue = DB::table('journal_entries')
            ->join('journal_entry_lines', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.company_id', $company->id)
            ->whereBetween('journal_entries.entry_date', [$startDate, $endDate])
            ->where('accounts.account_type', 'revenue')
            ->sum('journal_entry_lines.credit');

        // Expenses calculation
        $currentExpenses = DB::table('journal_entries')
            ->join('journal_entry_lines', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.company_id', $company->id)
            ->whereBetween('journal_entries.entry_date', [$startDate, $endDate])
            ->where('accounts.account_type', 'expense')
            ->sum('journal_entry_lines.debit');

        // Previous period for comparison
        $previousStartDate = $startDate->copy()->sub($endDate->diffInDays($startDate), 'days');
        $previousEndDate = $startDate->copy();

        $previousRevenue = DB::table('journal_entries')
            ->join('journal_entry_lines', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.company_id', $company->id)
            ->whereBetween('journal_entries.entry_date', [$previousStartDate, $previousEndDate])
            ->where('accounts.account_type', 'revenue')
            ->sum('journal_entry_lines.credit');

        $previousExpenses = DB::table('journal_entries')
            ->join('journal_entry_lines', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.company_id', $company->id)
            ->whereBetween('journal_entries.entry_date', [$previousStartDate, $previousEndDate])
            ->where('accounts.account_type', 'expense')
            ->sum('journal_entry_lines.debit');

        // Net profit
        $netProfit = $currentRevenue - $currentExpenses;
        $previousNetProfit = $previousRevenue - $previousExpenses;

        // Cash flow (mock calculation)
        $cashFlow = 125000000; // 12.5 lakh in paisa
        $previousCashFlow = 118000000; // 11.8 lakh in paisa

        return [
            'totalRevenue' => (int) $currentRevenue,
            'revenueDelta' => $previousRevenue > 0 ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1) : 0,
            'revenueSparkline' => $this->generateSparklineData(7),
            'totalExpenses' => (int) $currentExpenses,
            'expensesDelta' => $previousExpenses > 0 ? round((($currentExpenses - $previousExpenses) / $previousExpenses) * 100, 1) : 0,
            'expensesSparkline' => $this->generateSparklineData(7),
            'netProfit' => (int) $netProfit,
            'profitDelta' => $previousNetProfit != 0 ? round((($netProfit - $previousNetProfit) / abs($previousNetProfit)) * 100, 1) : 0,
            'cashFlow' => $cashFlow,
            'cashFlowDelta' => $previousCashFlow > 0 ? round((($cashFlow - $previousCashFlow) / $previousCashFlow) * 100, 1) : 0,
        ];
    }

    /**
     * Get chart data for profit & loss trend.
     */
    private function getChartData($company, $startDate, $endDate): array
    {
        $days = $endDate->diffInDays($startDate);
        $labels = [];
        $revenue = [];
        $expenses = [];

        for ($i = 0; $i <= $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $labels[] = $date->format('M j');
            
            // Mock data for now
            $revenue[] = rand(50000, 200000);
            $expenses[] = rand(30000, 150000);
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'expenses' => $expenses,
        ];
    }

    /**
     * Get account balances.
     */
    private function getAccountBalances($company): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Cash at Bank',
                'type' => 'Asset',
                'balance' => 125000000, // 12.5 lakh in paisa
                'change' => 5.2,
                'color' => '#0F766E',
            ],
            [
                'id' => 2,
                'name' => 'Accounts Receivable',
                'type' => 'Asset',
                'balance' => 87000000, // 8.7 lakh in paisa
                'change' => -2.1,
                'color' => '#14B8A6',
            ],
            [
                'id' => 3,
                'name' => 'Inventory',
                'type' => 'Asset',
                'balance' => 156000000, // 15.6 lakh in paisa
                'change' => 8.7,
                'color' => '#5EEAD4',
            ],
            [
                'id' => 4,
                'name' => 'Accounts Payable',
                'type' => 'Liability',
                'balance' => 45000000, // 4.5 lakh in paisa
                'change' => -1.5,
                'color' => '#CA8A04',
            ],
        ];
    }

    /**
     * Get financial ratios.
     */
    private function getFinancialRatios($company): array
    {
        return [
            'currentRatio' => 2.45,
            'quickRatio' => 1.87,
            'debtToEquity' => 35.2,
            'profitMargin' => 18.5,
        ];
    }

    /**
     * Get recent transactions.
     */
    private function getRecentTransactions($company): array
    {
        return [
            [
                'id' => 1,
                'description' => 'Sales Invoice #INV-001',
                'account' => 'Sales Revenue',
                'type' => 'credit',
                'amount' => 125000000, // 12.5 lakh in paisa
                'status' => 'Posted',
                'date' => Carbon::now()->subHours(2),
            ],
            [
                'id' => 2,
                'description' => 'Office Rent Payment',
                'account' => 'Rent Expense',
                'type' => 'debit',
                'amount' => 50000000, // 5 lakh in paisa
                'status' => 'Posted',
                'date' => Carbon::now()->subHours(6),
            ],
            [
                'id' => 3,
                'description' => 'Equipment Purchase',
                'account' => 'Equipment',
                'type' => 'debit',
                'amount' => 75000000, // 7.5 lakh in paisa
                'status' => 'Pending',
                'date' => Carbon::now()->subHours(12),
            ],
            [
                'id' => 4,
                'description' => 'Customer Payment',
                'account' => 'Cash at Bank',
                'type' => 'debit',
                'amount' => 98000000, // 9.8 lakh in paisa
                'status' => 'Posted',
                'date' => Carbon::now()->subDay(),
            ],
        ];
    }

    /**
     * Get expense categories.
     */
    private function getExpenseCategories($company, $startDate, $endDate): array
    {
        return [
            ['name' => 'Salaries & Benefits', 'amount' => 185000000, 'percentage' => 42, 'color' => '#0F766E'],
            ['name' => 'Rent & Utilities', 'amount' => 89000000, 'percentage' => 20, 'color' => '#14B8A6'],
            ['name' => 'Marketing', 'amount' => 67000000, 'percentage' => 15, 'color' => '#5EEAD4'],
            ['name' => 'Office Supplies', 'amount' => 45000000, 'percentage' => 10, 'color' => '#99F6E4'],
            ['name' => 'Travel & Entertainment', 'amount' => 34000000, 'percentage' => 8, 'color' => '#CCFBF1'],
            ['name' => 'Others', 'amount' => 22000000, 'percentage' => 5, 'color' => '#F0FDFA'],
        ];
    }

    /**
     * Get tax summary.
     */
    private function getTaxSummary($company): array
    {
        return [
            'vatCollected' => 45000000, // 4.5 lakh in paisa
            'vatPaid' => 28000000, // 2.8 lakh in paisa
            'netVatPayable' => 17000000, // 1.7 lakh in paisa
            'dueDate' => Carbon::now()->addDays(15),
            'upcomingObligations' => [
                [
                    'id' => 1,
                    'type' => 'Income Tax',
                    'amount' => 125000000, // 12.5 lakh in paisa
                    'dueDate' => Carbon::now()->addDays(30),
                ],
                [
                    'id' => 2,
                    'type' => 'VAT Return',
                    'amount' => 17000000, // 1.7 lakh in paisa
                    'dueDate' => Carbon::now()->addDays(15),
                ],
            ],
        ];
    }

    /**
     * Generate sparkline data for charts.
     */
    private function generateSparklineData(int $points): array
    {
        $data = [];
        for ($i = 0; $i < $points; $i++) {
            $data[] = rand(20, 100);
        }
        return $data;
    }
}