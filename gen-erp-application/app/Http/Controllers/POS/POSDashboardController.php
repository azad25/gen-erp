<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class POSDashboardController extends Controller
{
    /**
     * Display the POS dashboard.
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

        return Inertia::render('POS/Dashboard', [
            'metrics' => $this->getPOSMetrics($company, $startDate, $endDate),
            'chartData' => $this->getChartData($company, $startDate, $endDate),
            'topProducts' => $this->getTopProducts($company, $startDate, $endDate),
            'recentSales' => $this->getRecentSales($company),
            'activeSessions' => $this->getActiveSessions($company),
            'hourlyPerformance' => $this->getHourlyPerformance($company, $startDate, $endDate),
        ]);
    }

    /**
     * Get POS metrics for the dashboard.
     */
    private function getPOSMetrics($company, $startDate, $endDate): array
    {
        // Current period metrics
        $currentRevenue = DB::table('pos_sales')
            ->where('company_id', $company->id)
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->where('status', '!=', 'voided')
            ->sum('total_amount');

        $currentSales = DB::table('pos_sales')
            ->where('company_id', $company->id)
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->where('status', '!=', 'voided')
            ->count();

        // Previous period for comparison
        $previousStartDate = $startDate->copy()->sub($endDate->diffInDays($startDate), 'days');
        $previousEndDate = $startDate->copy();

        $previousRevenue = DB::table('pos_sales')
            ->where('company_id', $company->id)
            ->whereBetween('sale_date', [$previousStartDate, $previousEndDate])
            ->where('status', '!=', 'voided')
            ->sum('total_amount');

        $previousSales = DB::table('pos_sales')
            ->where('company_id', $company->id)
            ->whereBetween('sale_date', [$previousStartDate, $previousEndDate])
            ->where('status', '!=', 'voided')
            ->count();

        // Active sessions
        $activeSessions = DB::table('pos_sessions')
            ->where('company_id', $company->id)
            ->where('status', 'open')
            ->count();

        // Average transaction value
        $avgTransaction = $currentSales > 0 ? $currentRevenue / $currentSales : 0;
        $previousAvgTransaction = $previousSales > 0 ? $previousRevenue / $previousSales : 0;

        return [
            'totalRevenue' => (int) $currentRevenue,
            'revenueDelta' => $previousRevenue > 0 ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1) : 0,
            'revenueSparkline' => $this->generateSparklineData(7),
            'totalSales' => $currentSales,
            'salesDelta' => $previousSales > 0 ? round((($currentSales - $previousSales) / $previousSales) * 100, 1) : 0,
            'salesSparkline' => $this->generateSparklineData(7),
            'activeSessions' => $activeSessions,
            'avgTransaction' => (int) $avgTransaction,
            'avgTransactionDelta' => $previousAvgTransaction > 0 ? round((($avgTransaction - $previousAvgTransaction) / $previousAvgTransaction) * 100, 1) : 0,
        ];
    }

    /**
     * Get chart data for revenue trend.
     */
    private function getChartData($company, $startDate, $endDate): array
    {
        $days = $endDate->diffInDays($startDate);
        $labels = [];
        $revenue = [];

        for ($i = 0; $i <= $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $labels[] = $date->format('M j');
            
            $dayRevenue = DB::table('pos_sales')
                ->where('company_id', $company->id)
                ->whereDate('sale_date', $date)
                ->where('status', '!=', 'voided')
                ->sum('total_amount');
                
            $revenue[] = (int) ($dayRevenue / 100); // Convert to display currency
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
        ];
    }

    /**
     * Get top selling products.
     */
    private function getTopProducts($company, $startDate, $endDate): array
    {
        return DB::table('pos_sale_items')
            ->join('pos_sales', 'pos_sale_items.pos_sale_id', '=', 'pos_sales.id')
            ->join('products', 'pos_sale_items.product_id', '=', 'products.id')
            ->where('pos_sales.company_id', $company->id)
            ->whereBetween('pos_sales.sale_date', [$startDate, $endDate])
            ->where('pos_sales.status', '!=', 'voided')
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(pos_sale_items.quantity) as sales'),
                DB::raw('SUM(pos_sale_items.line_total) as revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sales' => (int) $product->sales,
                    'revenue' => (int) $product->revenue,
                ];
            })
            ->toArray();
    }

    /**
     * Get recent POS sales.
     */
    private function getRecentSales($company): array
    {
        return DB::table('pos_sales')
            ->join('pos_sessions', 'pos_sales.pos_session_id', '=', 'pos_sessions.id')
            ->leftJoin('branches', 'pos_sessions.branch_id', '=', 'branches.id')
            ->where('pos_sales.company_id', $company->id)
            ->select(
                'pos_sales.id',
                'pos_sales.sale_number',
                'pos_sales.total_amount',
                'pos_sales.payment_method_id',
                'pos_sales.status',
                'pos_sales.sale_date',
                'pos_sessions.id as session_id',
                'branches.name as branch_name'
            )
            ->orderByDesc('pos_sales.created_at')
            ->limit(10)
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'receiptNumber' => $sale->sale_number,
                    'amount' => (int) $sale->total_amount,
                    'paymentMethod' => $sale->payment_method_id ? 'Payment Method #' . $sale->payment_method_id : 'Cash',
                    'status' => ucfirst($sale->status),
                    'date' => $sale->sale_date,
                    'session' => $sale->branch_name ? $sale->branch_name . ' - Session #' . $sale->session_id : 'Session #' . $sale->session_id,
                ];
            })
            ->toArray();
    }

    /**
     * Get active POS sessions.
     */
    private function getActiveSessions($company): array
    {
        return DB::table('pos_sessions')
            ->leftJoin('users', 'pos_sessions.opened_by', '=', 'users.id')
            ->leftJoin('branches', 'pos_sessions.branch_id', '=', 'branches.id')
            ->where('pos_sessions.company_id', $company->id)
            ->where('pos_sessions.status', 'open')
            ->select(
                'pos_sessions.id',
                'pos_sessions.opening_cash',
                'pos_sessions.opened_at',
                'users.name as cashier',
                'branches.name as branch_name'
            )
            ->get()
            ->map(function ($session) use ($company) {
                // Get session sales
                $sessionSales = DB::table('pos_sales')
                    ->where('pos_session_id', $session->id)
                    ->where('status', '!=', 'voided')
                    ->sum('total_amount');

                return [
                    'id' => $session->id,
                    'name' => $session->branch_name ? $session->branch_name . ' - Session #' . $session->id : 'Session #' . $session->id,
                    'cashier' => $session->cashier ?? 'Unknown',
                    'openingBalance' => (int) $session->opening_cash,
                    'currentSales' => (int) $sessionSales,
                    'openedAt' => $session->opened_at,
                ];
            })
            ->toArray();
    }

    /**
     * Get hourly performance data.
     */
    private function getHourlyPerformance($company, $startDate, $endDate): array
    {
        $hourlyData = DB::table('pos_sales')
            ->where('company_id', $company->id)
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->where('status', '!=', 'voided')
            ->select(
                DB::raw('HOUR(sale_date) as hour'),
                DB::raw('COUNT(*) as transactions'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $hours = [];
        $transactions = [];
        $revenue = [];

        for ($i = 0; $i < 24; $i++) {
            $hours[] = sprintf('%02d:00', $i);
            $hourData = $hourlyData->firstWhere('hour', $i);
            $transactions[] = $hourData ? (int) $hourData->transactions : 0;
            $revenue[] = $hourData ? (int) ($hourData->revenue / 100) : 0;
        }

        return [
            'hours' => $hours,
            'transactions' => $transactions,
            'revenue' => $revenue,
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
