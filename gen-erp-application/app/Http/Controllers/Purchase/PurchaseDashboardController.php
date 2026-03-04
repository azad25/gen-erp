<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PurchaseDashboardController extends Controller
{
    /**
     * Display the purchase dashboard.
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

        return Inertia::render('Purchase/Dashboard', [
            'metrics' => $this->getPurchaseMetrics($company, $startDate, $endDate),
            'chartData' => $this->getChartData($company, $startDate, $endDate),
            'topSuppliers' => $this->getTopSuppliers($company, $startDate, $endDate),
            'recentOrders' => $this->getRecentOrders($company),
            'inventoryStatus' => $this->getInventoryStatus($company),
            'purchaseCategories' => $this->getPurchaseCategories($company, $startDate, $endDate),
            'approvalWorkflow' => $this->getApprovalWorkflow($company),
        ]);
    }

    /**
     * Get purchase metrics for the dashboard.
     */
    private function getPurchaseMetrics($company, $startDate, $endDate): array
    {
        // Current period metrics
        $currentPurchases = DB::table('purchase_orders')
            ->where('company_id', $company->id)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $currentOrders = DB::table('purchase_orders')
            ->where('company_id', $company->id)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->count();

        // Previous period for comparison
        $previousStartDate = $startDate->copy()->sub($endDate->diffInDays($startDate), 'days');
        $previousEndDate = $startDate->copy();

        $previousPurchases = DB::table('purchase_orders')
            ->where('company_id', $company->id)
            ->whereBetween('order_date', [$previousStartDate, $previousEndDate])
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $previousOrders = DB::table('purchase_orders')
            ->where('company_id', $company->id)
            ->whereBetween('order_date', [$previousStartDate, $previousEndDate])
            ->where('status', '!=', 'cancelled')
            ->count();

        // Pending approvals
        $pendingApprovals = DB::table('purchase_orders')
            ->where('company_id', $company->id)
            ->where('status', 'pending')
            ->count();

        // Cost savings (mock calculation)
        $costSavings = 45000000; // 4.5 lakh in paisa
        $previousCostSavings = 38000000; // 3.8 lakh in paisa

        return [
            'totalPurchases' => (int) $currentPurchases,
            'purchasesDelta' => $previousPurchases > 0 ? round((($currentPurchases - $previousPurchases) / $previousPurchases) * 100, 1) : 0,
            'purchasesSparkline' => $this->generateSparklineData(7),
            'totalOrders' => $currentOrders,
            'ordersDelta' => $previousOrders > 0 ? round((($currentOrders - $previousOrders) / $previousOrders) * 100, 1) : 0,
            'ordersSparkline' => $this->generateSparklineData(7),
            'pendingApprovals' => $pendingApprovals,
            'costSavings' => $costSavings,
            'savingsDelta' => $previousCostSavings > 0 ? round((($costSavings - $previousCostSavings) / $previousCostSavings) * 100, 1) : 0,
        ];
    }

    /**
     * Get chart data for purchase trend.
     */
    private function getChartData($company, $startDate, $endDate): array
    {
        $days = $endDate->diffInDays($startDate);
        $labels = [];
        $purchases = [];

        for ($i = 0; $i <= $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $labels[] = $date->format('M j');
            
            $dayPurchases = DB::table('purchase_orders')
                ->where('company_id', $company->id)
                ->whereDate('order_date', $date)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');
                
            $purchases[] = (int) ($dayPurchases / 100); // Convert to display currency
        }

        return [
            'labels' => $labels,
            'purchases' => $purchases,
        ];
    }

    /**
     * Get top suppliers.
     */
    private function getTopSuppliers($company, $startDate, $endDate): array
    {
        return DB::table('purchase_orders')
            ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->where('purchase_orders.company_id', $company->id)
            ->whereBetween('purchase_orders.order_date', [$startDate, $endDate])
            ->where('purchase_orders.status', '!=', 'cancelled')
            ->select(
                'suppliers.id',
                'suppliers.name',
                DB::raw('COUNT(purchase_orders.id) as orders'),
                DB::raw('SUM(purchase_orders.total_amount) as amount')
            )
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('amount')
            ->limit(5)
            ->get()
            ->map(function ($supplier, $index) {
                return [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'orders' => (int) $supplier->orders,
                    'amount' => (int) $supplier->amount,
                    'rating' => 4.5 - ($index * 0.2), // Mock rating
                ];
            })
            ->toArray();
    }

    /**
     * Get recent purchase orders.
     */
    private function getRecentOrders($company): array
    {
        return DB::table('purchase_orders')
            ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->where('purchase_orders.company_id', $company->id)
            ->select(
                'purchase_orders.id',
                'purchase_orders.reference_number as order_number',
                'suppliers.name as supplier',
                'purchase_orders.total_amount as amount',
                'purchase_orders.status',
                'purchase_orders.order_date as date'
            )
            ->orderByDesc('purchase_orders.created_at')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'orderNumber' => $order->order_number,
                    'supplier' => $order->supplier,
                    'amount' => (int) $order->amount,
                    'status' => ucfirst($order->status),
                    'date' => $order->date,
                ];
            })
            ->toArray();
    }

    /**
     * Get inventory status.
     */
    private function getInventoryStatus($company): array
    {
        // Get products with their total stock across all warehouses
        $productsWithStock = DB::table('products')
            ->leftJoin('stock_levels', 'products.id', '=', 'stock_levels.product_id')
            ->where('products.company_id', $company->id)
            ->where('products.track_inventory', true)
            ->select(
                'products.id',
                'products.name',
                'products.low_stock_threshold',
                DB::raw('COALESCE(SUM(stock_levels.quantity), 0) as current_stock')
            )
            ->groupBy('products.id', 'products.name', 'products.low_stock_threshold');

        $lowStock = (clone $productsWithStock)
            ->havingRaw('current_stock <= products.low_stock_threshold')
            ->havingRaw('current_stock > 0')
            ->count();

        $outOfStock = (clone $productsWithStock)
            ->havingRaw('current_stock <= 0')
            ->count();

        $criticalItems = DB::table('products')
            ->leftJoin('stock_levels', 'products.id', '=', 'stock_levels.product_id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->where('products.company_id', $company->id)
            ->where('products.track_inventory', true)
            ->select(
                'products.id',
                'products.name',
                'product_categories.name as category',
                DB::raw('COALESCE(SUM(stock_levels.quantity), 0) as stock'),
                'products.low_stock_threshold as minStock'
            )
            ->groupBy('products.id', 'products.name', 'product_categories.name', 'products.low_stock_threshold')
            ->havingRaw('stock <= products.low_stock_threshold')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category ?? 'General',
                    'stock' => (int) $item->stock,
                    'minStock' => (int) $item->minStock,
                ];
            })
            ->toArray();

        return [
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
            'criticalItems' => $criticalItems,
        ];
    }

    /**
     * Get purchase categories breakdown.
     */
    private function getPurchaseCategories($company, $startDate, $endDate): array
    {
        // Mock data for purchase categories
        return [
            ['name' => 'Raw Materials', 'amount' => 125000000, 'percentage' => 45, 'color' => '#0F766E'],
            ['name' => 'Office Supplies', 'amount' => 67000000, 'percentage' => 24, 'color' => '#14B8A6'],
            ['name' => 'Equipment', 'amount' => 45000000, 'percentage' => 16, 'color' => '#5EEAD4'],
            ['name' => 'Services', 'amount' => 28000000, 'percentage' => 10, 'color' => '#99F6E4'],
            ['name' => 'Others', 'amount' => 14000000, 'percentage' => 5, 'color' => '#CCFBF1'],
        ];
    }

    /**
     * Get approval workflow status.
     */
    private function getApprovalWorkflow($company): array
    {
        return [
            [
                'name' => 'Department Head',
                'description' => 'Initial approval by department head',
                'pending' => 12,
                'completed' => true,
                'progress' => 85,
            ],
            [
                'name' => 'Finance Review',
                'description' => 'Budget and financial review',
                'pending' => 8,
                'completed' => true,
                'progress' => 72,
            ],
            [
                'name' => 'Management Approval',
                'description' => 'Final approval by management',
                'pending' => 5,
                'completed' => false,
                'progress' => 45,
            ],
            [
                'name' => 'Procurement',
                'description' => 'Purchase order creation',
                'pending' => 3,
                'completed' => false,
                'progress' => 20,
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