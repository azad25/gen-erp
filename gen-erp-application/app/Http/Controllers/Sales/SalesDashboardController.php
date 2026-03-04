<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesDashboardController extends Controller
{
    /**
     * Display the sales dashboard.
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

        return Inertia::render('Sales/Dashboard', [
            'metrics' => $this->getSalesMetrics($company, $startDate, $endDate),
            'chartData' => $this->getChartData($company, $startDate, $endDate),
            'topProducts' => $this->getTopProducts($company, $startDate, $endDate),
            'salesTeam' => $this->getSalesTeamPerformance($company, $startDate, $endDate),
            'recentOrders' => $this->getRecentOrders($company),
            'salesFunnel' => $this->getSalesFunnel($company),
            'customerInsights' => $this->getCustomerInsights($company, $startDate, $endDate),
        ]);
    }

    /**
     * Get sales metrics for the dashboard.
     */
    private function getSalesMetrics($company, $startDate, $endDate): array
    {
        // Current period metrics
        $currentRevenue = DB::table('sales_orders')
            ->where('company_id', $company->id)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $currentOrders = DB::table('sales_orders')
            ->where('company_id', $company->id)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->count();

        // Previous period for comparison
        $previousStartDate = $startDate->copy()->sub($endDate->diffInDays($startDate), 'days');
        $previousEndDate = $startDate->copy();

        $previousRevenue = DB::table('sales_orders')
            ->where('company_id', $company->id)
            ->whereBetween('order_date', [$previousStartDate, $previousEndDate])
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $previousOrders = DB::table('sales_orders')
            ->where('company_id', $company->id)
            ->whereBetween('order_date', [$previousStartDate, $previousEndDate])
            ->where('status', '!=', 'cancelled')
            ->count();

        // Outstanding invoices
        $outstanding = DB::table('invoices')
            ->where('company_id', $company->id)
            ->where('status', 'sent')
            ->sum('total_amount');

        // Conversion rate (mock data for now)
        $conversionRate = 24.5;
        $previousConversionRate = 22.1;

        return [
            'totalRevenue' => (int) $currentRevenue,
            'revenueDelta' => $previousRevenue > 0 ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1) : 0,
            'revenueSparkline' => $this->generateSparklineData(7),
            'totalOrders' => $currentOrders,
            'ordersDelta' => $previousOrders > 0 ? round((($currentOrders - $previousOrders) / $previousOrders) * 100, 1) : 0,
            'ordersSparkline' => $this->generateSparklineData(7),
            'outstanding' => (int) $outstanding,
            'conversionRate' => $conversionRate,
            'conversionDelta' => round($conversionRate - $previousConversionRate, 1),
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
            
            $dayRevenue = DB::table('sales_orders')
                ->where('company_id', $company->id)
                ->whereDate('order_date', $date)
                ->where('status', '!=', 'cancelled')
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
        return DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->join('products', 'sales_order_items.product_id', '=', 'products.id')
            ->where('sales_orders.company_id', $company->id)
            ->whereBetween('sales_orders.order_date', [$startDate, $endDate])
            ->where('sales_orders.status', '!=', 'cancelled')
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(sales_order_items.quantity) as sales'),
                DB::raw('SUM(sales_order_items.line_total) as revenue')
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
     * Get sales team performance (mock data for now).
     */
    private function getSalesTeamPerformance($company, $startDate, $endDate): array
    {
        // This would typically come from a sales_representatives table
        // For now, returning mock data
        return [
            [
                'id' => 1,
                'name' => 'Ahmed Rahman',
                'role' => 'Senior Sales Rep',
                'sales' => 125000000, // 12.5 lakh in paisa
                'progress' => 85,
            ],
            [
                'id' => 2,
                'name' => 'Fatima Khan',
                'role' => 'Sales Rep',
                'sales' => 98000000, // 9.8 lakh in paisa
                'progress' => 72,
            ],
            [
                'id' => 3,
                'name' => 'Mohammad Ali',
                'role' => 'Junior Sales Rep',
                'sales' => 67000000, // 6.7 lakh in paisa
                'progress' => 58,
            ],
        ];
    }

    /**
     * Get recent sales orders.
     */
    private function getRecentOrders($company): array
    {
        return DB::table('sales_orders')
            ->join('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->where('sales_orders.company_id', $company->id)
            ->select(
                'sales_orders.id',
                'sales_orders.reference_number',
                'customers.name as customer',
                'sales_orders.total_amount as amount',
                'sales_orders.status',
                'sales_orders.order_date as date'
            )
            ->orderByDesc('sales_orders.created_at')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'orderNumber' => $order->reference_number,
                    'customer' => $order->customer,
                    'amount' => (int) $order->amount,
                    'status' => ucfirst($order->status),
                    'date' => $order->date,
                ];
            })
            ->toArray();
    }

    /**
     * Get sales funnel data (mock data for now).
     */
    private function getSalesFunnel($company): array
    {
        return [
            ['name' => 'Leads', 'count' => 1250, 'percentage' => 100],
            ['name' => 'Qualified', 'count' => 875, 'percentage' => 70],
            ['name' => 'Proposals', 'count' => 438, 'percentage' => 35],
            ['name' => 'Negotiations', 'count' => 188, 'percentage' => 15],
            ['name' => 'Closed Won', 'count' => 125, 'percentage' => 10],
        ];
    }

    /**
     * Get customer insights.
     */
    private function getCustomerInsights($company, $startDate, $endDate): array
    {
        $newCustomers = DB::table('customers')
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $repeatCustomers = DB::table('sales_orders')
            ->where('company_id', $company->id)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->select('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        $avgOrderValue = DB::table('sales_orders')
            ->where('company_id', $company->id)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->avg('total_amount');

        return [
            'newCustomers' => $newCustomers,
            'newCustomersDelta' => 15.2, // Mock data
            'repeatCustomers' => $repeatCustomers,
            'repeatCustomersDelta' => 8.7, // Mock data
            'avgOrderValue' => (int) ($avgOrderValue ?? 0),
            'avgOrderValueDelta' => -2.3, // Mock data
            'customerLifetime' => 180, // Mock data
            'customerLifetimeDelta' => 12.5, // Mock data
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