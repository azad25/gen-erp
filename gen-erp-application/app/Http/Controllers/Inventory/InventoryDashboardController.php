<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryDashboardController extends Controller
{
    /**
     * Display the inventory dashboard.
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

        return Inertia::render('Inventory/Dashboard', [
            'metrics' => $this->getInventoryMetrics($company, $startDate, $endDate),
            'chartData' => $this->getChartData($company, $startDate, $endDate),
            'warehouses' => $this->getWarehouseOverview($company),
            'criticalStock' => $this->getCriticalStock($company),
            'topMovingItems' => $this->getTopMovingItems($company, $startDate, $endDate),
            'recentMovements' => $this->getRecentMovements($company),
            'abcAnalysis' => $this->getABCAnalysis($company),
        ]);
    }

    /**
     * Get inventory metrics for the dashboard.
     */
    private function getInventoryMetrics($company, $startDate, $endDate): array
    {
        // Total stock value - join with stock_levels to get current stock
        $totalStockValue = DB::table('products')
            ->leftJoin('stock_levels', 'products.id', '=', 'stock_levels.product_id')
            ->where('products.company_id', $company->id)
            ->where('products.track_inventory', true)
            ->selectRaw('SUM(COALESCE(stock_levels.quantity, 0) * products.cost_price) as total_value')
            ->value('total_value') ?? 0;

        // Previous period stock value (mock calculation)
        $previousStockValue = $totalStockValue * 0.95; // 5% less than current

        // Total products
        $totalProducts = DB::table('products')
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->count();

        $previousProducts = $totalProducts - 5; // Mock previous count

        // Low stock items - products with stock <= low_stock_threshold
        $lowStockItems = DB::table('products')
            ->leftJoin('stock_levels', 'products.id', '=', 'stock_levels.product_id')
            ->where('products.company_id', $company->id)
            ->where('products.track_inventory', true)
            ->select(
                'products.id',
                'products.low_stock_threshold',
                DB::raw('COALESCE(SUM(stock_levels.quantity), 0) as current_stock')
            )
            ->groupBy('products.id', 'products.low_stock_threshold')
            ->havingRaw('current_stock <= products.low_stock_threshold')
            ->havingRaw('current_stock > 0')
            ->count();

        // Out of stock items
        $outOfStockItems = DB::table('products')
            ->leftJoin('stock_levels', 'products.id', '=', 'stock_levels.product_id')
            ->where('products.company_id', $company->id)
            ->where('products.track_inventory', true)
            ->select(
                'products.id',
                DB::raw('COALESCE(SUM(stock_levels.quantity), 0) as current_stock')
            )
            ->groupBy('products.id')
            ->havingRaw('current_stock <= 0')
            ->count();

        return [
            'totalStockValue' => (int) ($totalStockValue * 100), // Convert to paisa
            'stockValueDelta' => $previousStockValue > 0 ? round((($totalStockValue - $previousStockValue) / $previousStockValue) * 100, 1) : 0,
            'stockValueSparkline' => $this->generateSparklineData(7),
            'totalProducts' => $totalProducts,
            'productsDelta' => $previousProducts > 0 ? round((($totalProducts - $previousProducts) / $previousProducts) * 100, 1) : 0,
            'lowStockItems' => $lowStockItems,
            'outOfStockItems' => $outOfStockItems,
        ];
    }

    /**
     * Get chart data for stock movements.
     */
    private function getChartData($company, $startDate, $endDate): array
    {
        $days = $endDate->diffInDays($startDate);
        $labels = [];
        $stockIn = [];
        $stockOut = [];

        for ($i = 0; $i <= $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $labels[] = $date->format('M j');
            
            // Mock data for stock movements
            $stockIn[] = rand(50, 200);
            $stockOut[] = rand(30, 180);
        }

        return [
            'labels' => $labels,
            'stockIn' => $stockIn,
            'stockOut' => $stockOut,
        ];
    }

    /**
     * Get warehouse overview.
     */
    private function getWarehouseOverview($company): array
    {
        return DB::table('warehouses')
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->select('id', 'name', 'address')
            ->get()
            ->map(function ($warehouse, $index) {
                // Mock data for warehouse utilization
                $utilization = rand(45, 95);
                $itemCount = rand(150, 500);
                $value = rand(500000, 2000000) * 100; // Convert to paisa

                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'location' => $warehouse->address ?? 'Main Location',
                    'utilization' => $utilization,
                    'itemCount' => $itemCount,
                    'value' => $value,
                ];
            })
            ->toArray();
    }

    /**
     * Get critical stock levels.
     */
    private function getCriticalStock($company): array
    {
        return DB::table('products')
            ->leftJoin('stock_levels', 'products.id', '=', 'stock_levels.product_id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->where('products.company_id', $company->id)
            ->where('products.track_inventory', true)
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'product_categories.name as category',
                DB::raw('COALESCE(SUM(stock_levels.quantity), 0) as current_stock'),
                'products.low_stock_threshold as minimum_stock'
            )
            ->groupBy('products.id', 'products.name', 'products.sku', 'product_categories.name', 'products.low_stock_threshold')
            ->havingRaw('current_stock <= products.low_stock_threshold')
            ->orderBy('current_stock')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $status = 'low_stock';
                if ($item->current_stock <= 0) {
                    $status = 'out_of_stock';
                } elseif ($item->current_stock <= ($item->minimum_stock * 0.5)) {
                    $status = 'critical';
                }

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'category' => $item->category ?? 'General',
                    'currentStock' => (int) $item->current_stock,
                    'minStock' => (int) $item->minimum_stock,
                    'status' => $status,
                ];
            })
            ->toArray();
    }

    /**
     * Get top moving items.
     */
    private function getTopMovingItems($company, $startDate, $endDate): array
    {
        // Mock data for top moving items
        return [
            [
                'id' => 1,
                'name' => 'Premium Widget A',
                'category' => 'Electronics',
                'movements' => 245,
                'velocity' => 85,
            ],
            [
                'id' => 2,
                'name' => 'Standard Component B',
                'category' => 'Components',
                'movements' => 198,
                'velocity' => 72,
            ],
            [
                'id' => 3,
                'name' => 'Deluxe Package C',
                'category' => 'Packages',
                'movements' => 167,
                'velocity' => 65,
            ],
            [
                'id' => 4,
                'name' => 'Basic Tool D',
                'category' => 'Tools',
                'movements' => 134,
                'velocity' => 58,
            ],
            [
                'id' => 5,
                'name' => 'Advanced Kit E',
                'category' => 'Kits',
                'movements' => 112,
                'velocity' => 45,
            ],
        ];
    }

    /**
     * Get recent stock movements.
     */
    private function getRecentMovements($company): array
    {
        // Mock data for recent movements
        return [
            [
                'id' => 1,
                'product' => 'Premium Widget A',
                'type' => 'in',
                'quantity' => 50,
                'reason' => 'Purchase Receipt',
                'warehouse' => 'Main Warehouse',
                'date' => Carbon::now()->subHours(2),
            ],
            [
                'id' => 2,
                'product' => 'Standard Component B',
                'type' => 'out',
                'quantity' => 25,
                'reason' => 'Sales Order',
                'warehouse' => 'Main Warehouse',
                'date' => Carbon::now()->subHours(4),
            ],
            [
                'id' => 3,
                'product' => 'Deluxe Package C',
                'type' => 'in',
                'quantity' => 15,
                'reason' => 'Stock Adjustment',
                'warehouse' => 'Secondary Warehouse',
                'date' => Carbon::now()->subHours(6),
            ],
            [
                'id' => 4,
                'product' => 'Basic Tool D',
                'type' => 'out',
                'quantity' => 40,
                'reason' => 'Transfer Out',
                'warehouse' => 'Main Warehouse',
                'date' => Carbon::now()->subHours(8),
            ],
            [
                'id' => 5,
                'product' => 'Advanced Kit E',
                'type' => 'in',
                'quantity' => 30,
                'reason' => 'Return',
                'warehouse' => 'Main Warehouse',
                'date' => Carbon::now()->subHours(12),
            ],
        ];
    }

    /**
     * Get ABC analysis data.
     */
    private function getABCAnalysis($company): array
    {
        // Mock ABC analysis data
        return [
            'categoryA' => [
                'count' => 45,
                'percentage' => 80,
            ],
            'categoryB' => [
                'count' => 78,
                'percentage' => 15,
            ],
            'categoryC' => [
                'count' => 156,
                'percentage' => 5,
            ],
            'topItems' => [
                [
                    'id' => 1,
                    'name' => 'Premium Widget A',
                    'category' => 'Electronics',
                    'value' => 125000000, // 12.5 lakh in paisa
                    'turnover' => 8.5,
                ],
                [
                    'id' => 2,
                    'name' => 'Deluxe Package C',
                    'category' => 'Packages',
                    'value' => 98000000, // 9.8 lakh in paisa
                    'turnover' => 6.2,
                ],
                [
                    'id' => 3,
                    'name' => 'Advanced Kit E',
                    'category' => 'Kits',
                    'value' => 76000000, // 7.6 lakh in paisa
                    'turnover' => 4.8,
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