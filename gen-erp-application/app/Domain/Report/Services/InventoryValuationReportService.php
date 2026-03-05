<?php

namespace App\Domain\Report\Services;

use App\Domain\Auth\Models\Company;
use App\Domain\Inventory\Models\StockLayer;
use App\Domain\Inventory\Models\StockLayerAllocation;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Product\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Generates comprehensive inventory valuation reports with COGS breakdown and layer analysis.
 */
class InventoryValuationReportService
{
    /**
     * Generate inventory valuation report as of a specific date.
     *
     * @return array{
     *   as_of_date: string,
     *   company: string,
     *   products: array,
     *   summary: array{
     *     total_quantity: float,
     *     total_value: int,
     *     product_count: int,
     *     warehouse_count: int,
     *     average_unit_cost: float
     *   }
     * }
     */
    public function inventoryValuation(Company $company, ?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();

        // Get all stock layers as of the date
        $stockLayers = StockLayer::where('company_id', $company->id)
            ->where('layer_date', '<=', $asOfDate)
            ->where('quantity_remaining', '>', 0)
            ->with(['product', 'warehouse'])
            ->get();

        $productValuation = $stockLayers->groupBy('product_id')->map(function ($productLayers, $productId) {
            $product = $productLayers->first()->product;
            
            $warehouseBreakdown = $productLayers->groupBy('warehouse_id')->map(function ($warehouseLayers, $warehouseId) {
                $warehouse = $warehouseLayers->first()->warehouse;
                $totalQty = $warehouseLayers->sum('quantity_remaining');
                $totalValue = $warehouseLayers->sum(function ($layer) {
                    return $layer->quantity_remaining * $layer->unit_cost;
                });
                $avgCost = $totalQty > 0 ? $totalValue / $totalQty : 0;

                return [
                    'warehouse_id' => $warehouseId,
                    'warehouse_name' => $warehouse->name ?? 'Unknown Warehouse',
                    'quantity' => $totalQty,
                    'total_value' => $totalValue,
                    'average_unit_cost' => round($avgCost, 2),
                    'layer_count' => $warehouseLayers->count(),
                    'oldest_layer_date' => $warehouseLayers->min('layer_date'),
                    'newest_layer_date' => $warehouseLayers->max('layer_date'),
                ];
            });

            $totalQty = $productLayers->sum('quantity_remaining');
            $totalValue = $productLayers->sum(function ($layer) {
                return $layer->quantity_remaining * $layer->unit_cost;
            });
            $avgCost = $totalQty > 0 ? $totalValue / $totalQty : 0;

            return [
                'product_id' => $productId,
                'product_name' => $product->name ?? 'Unknown Product',
                'product_code' => $product->code ?? '',
                'unit' => $product->unit ?? 'pcs',
                'total_quantity' => $totalQty,
                'total_value' => $totalValue,
                'average_unit_cost' => round($avgCost, 2),
                'warehouse_breakdown' => $warehouseBreakdown->values()->toArray(),
                'layer_count' => $productLayers->count(),
            ];
        })->sortByDesc('total_value');

        // Calculate summary
        $summary = [
            'total_quantity' => $productValuation->sum('total_quantity'),
            'total_value' => $productValuation->sum('total_value'),
            'product_count' => $productValuation->count(),
            'warehouse_count' => $stockLayers->pluck('warehouse_id')->unique()->count(),
            'average_unit_cost' => $productValuation->count() > 0 
                ? $productValuation->sum('total_value') / $productValuation->sum('total_quantity') 
                : 0,
        ];

        return [
            'as_of_date' => $asOfDate->format('d M Y'),
            'company' => $company->name,
            'products' => $productValuation->values()->toArray(),
            'summary' => $summary,
        ];
    }

    /**
     * Generate COGS analysis report for a period.
     *
     * @return array{
     *   period: string,
     *   company: string,
     *   products: array,
     *   summary: array{
     *     total_cogs: int,
     *     total_quantity_sold: float,
     *     average_cogs_per_unit: float,
     *     product_count: int,
     *     allocation_count: int
     *   }
     * }
     */
    public function cogsAnalysis(Company $company, Carbon $fromDate, Carbon $toDate): array
    {
        // Get all stock layer allocations (COGS) for the period
        $allocations = StockLayerAllocation::whereHas('stockMovement', function ($q) use ($company, $fromDate, $toDate) {
            $q->where('company_id', $company->id)
                ->where('movement_type', 'out')
                ->whereBetween('movement_date', [$fromDate, $toDate]);
        })
            ->with(['stockMovement.product', 'stockLayer'])
            ->get();

        $productCogs = $allocations->groupBy('stockMovement.product_id')->map(function ($productAllocations, $productId) {
            $product = $productAllocations->first()->stockMovement->product;
            
            $totalCogs = $productAllocations->sum('cost_amount');
            $totalQty = $productAllocations->sum('qty');
            $avgCogsPerUnit = $totalQty > 0 ? $totalCogs / $totalQty : 0;

            // Analyze COGS by layer age
            $layerAnalysis = $productAllocations->groupBy('stockLayer.layer_date')->map(function ($layerAllocations, $layerDate) {
                return [
                    'layer_date' => $layerDate,
                    'quantity_consumed' => $layerAllocations->sum('qty'),
                    'cogs_amount' => $layerAllocations->sum('cost_amount'),
                    'average_unit_cost' => $layerAllocations->avg('unit_cost'),
                    'allocation_count' => $layerAllocations->count(),
                ];
            })->sortBy('layer_date');

            return [
                'product_id' => $productId,
                'product_name' => $product->name ?? 'Unknown Product',
                'product_code' => $product->code ?? '',
                'total_cogs' => $totalCogs,
                'quantity_sold' => $totalQty,
                'average_cogs_per_unit' => round($avgCogsPerUnit, 2),
                'allocation_count' => $productAllocations->count(),
                'layer_analysis' => $layerAnalysis->values()->toArray(),
                'oldest_layer_consumed' => $layerAnalysis->first()['layer_date'] ?? null,
                'newest_layer_consumed' => $layerAnalysis->last()['layer_date'] ?? null,
            ];
        })->sortByDesc('total_cogs');

        // Calculate summary
        $summary = [
            'total_cogs' => $productCogs->sum('total_cogs'),
            'total_quantity_sold' => $productCogs->sum('quantity_sold'),
            'average_cogs_per_unit' => $productCogs->count() > 0 
                ? $productCogs->sum('total_cogs') / $productCogs->sum('quantity_sold') 
                : 0,
            'product_count' => $productCogs->count(),
            'allocation_count' => $allocations->count(),
        ];

        return [
            'period' => $fromDate->format('d M Y') . ' - ' . $toDate->format('d M Y'),
            'company' => $company->name,
            'products' => $productCogs->values()->toArray(),
            'summary' => $summary,
        ];
    }

    /**
     * Generate detailed layer analysis for a specific product.
     */
    public function productLayerAnalysis(Company $company, int $productId, ?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();
        
        $product = Product::where('company_id', $company->id)->findOrFail($productId);

        // Get all layers for this product
        $layers = StockLayer::where('company_id', $company->id)
            ->where('product_id', $productId)
            ->where('layer_date', '<=', $asOfDate)
            ->with(['warehouse', 'sourceMovement'])
            ->orderBy('layer_date')
            ->get();

        $layerDetails = $layers->map(function ($layer) use ($asOfDate) {
            // Get allocations for this layer
            $allocations = StockLayerAllocation::where('layer_id', $layer->id)
                ->whereHas('stockMovement', function ($q) use ($asOfDate) {
                    $q->where('movement_date', '<=', $asOfDate);
                })
                ->with('stockMovement')
                ->get();

            $totalAllocated = $allocations->sum('qty');
            $totalCogsGenerated = $allocations->sum('cost_amount');

            return [
                'layer_id' => $layer->id,
                'layer_date' => $layer->layer_date,
                'warehouse_name' => $layer->warehouse->name ?? 'Unknown',
                'source_movement_type' => $layer->sourceMovement->type ?? 'unknown',
                'qty_in' => $layer->quantity_in,
                'qty_remaining' => $layer->quantity_remaining,
                'qty_allocated' => $totalAllocated,
                'unit_cost' => $layer->unit_cost,
                'total_value_remaining' => $layer->quantity_remaining * $layer->unit_cost,
                'total_cogs_generated' => $totalCogsGenerated,
                'allocation_count' => $allocations->count(),
                'is_fully_consumed' => $layer->quantity_remaining <= 0,
                'age_days' => $asOfDate->diffInDays(Carbon::parse($layer->layer_date)),
            ];
        });

        // Calculate summary
        $totalValue = $layers->sum(function ($layer) {
            return $layer->quantity_remaining * $layer->unit_cost;
        });
        $totalQty = $layers->sum('quantity_remaining');
        $avgCost = $totalQty > 0 ? $totalValue / $totalQty : 0;

        return [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'unit' => $product->unit,
            ],
            'as_of_date' => $asOfDate->format('d M Y'),
            'summary' => [
                'total_quantity' => $totalQty,
                'total_value' => $totalValue,
                'average_unit_cost' => round($avgCost, 2),
                'layer_count' => $layers->count(),
                'active_layers' => $layers->where('quantity_remaining', '>', 0)->count(),
                'oldest_layer_date' => $layers->min('layer_date'),
                'newest_layer_date' => $layers->max('layer_date'),
            ],
            'layers' => $layerDetails->toArray(),
        ];
    }

    /**
     * Generate inventory turnover analysis.
     */
    public function inventoryTurnoverAnalysis(Company $company, Carbon $fromDate, Carbon $toDate): array
    {
        // Get COGS for the period
        $cogsData = $this->cogsAnalysis($company, $fromDate, $toDate);
        
        // Get average inventory value for the period
        $startInventory = $this->inventoryValuation($company, $fromDate);
        $endInventory = $this->inventoryValuation($company, $toDate);
        
        $avgInventoryValue = ($startInventory['summary']['total_value'] + $endInventory['summary']['total_value']) / 2;
        
        $inventoryTurnover = $avgInventoryValue > 0 ? $cogsData['summary']['total_cogs'] / $avgInventoryValue : 0;
        $daysInPeriod = $fromDate->diffInDays($toDate);
        $daysSalesInInventory = $inventoryTurnover > 0 ? $daysInPeriod / $inventoryTurnover : 0;

        return [
            'period' => $fromDate->format('d M Y') . ' - ' . $toDate->format('d M Y'),
            'company' => $company->name,
            'metrics' => [
                'total_cogs' => $cogsData['summary']['total_cogs'],
                'average_inventory_value' => $avgInventoryValue,
                'inventory_turnover_ratio' => round($inventoryTurnover, 2),
                'days_sales_in_inventory' => round($daysSalesInInventory, 1),
                'start_inventory_value' => $startInventory['summary']['total_value'],
                'end_inventory_value' => $endInventory['summary']['total_value'],
            ],
            'interpretation' => [
                'turnover_rating' => $this->getTurnoverRating($inventoryTurnover),
                'efficiency_note' => $this->getEfficiencyNote($daysSalesInInventory),
            ],
        ];
    }

    private function getTurnoverRating(float $turnover): string
    {
        if ($turnover >= 12) return 'Excellent (12+ times/year)';
        if ($turnover >= 6) return 'Good (6-12 times/year)';
        if ($turnover >= 4) return 'Average (4-6 times/year)';
        if ($turnover >= 2) return 'Below Average (2-4 times/year)';
        return 'Poor (< 2 times/year)';
    }

    private function getEfficiencyNote(float $days): string
    {
        if ($days <= 30) return 'Very efficient - inventory moves quickly';
        if ($days <= 60) return 'Good efficiency - reasonable inventory velocity';
        if ($days <= 90) return 'Average efficiency - monitor slow-moving items';
        if ($days <= 180) return 'Below average - consider inventory optimization';
        return 'Poor efficiency - significant slow-moving inventory';
    }
}