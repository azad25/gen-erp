<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Auth\Models\Company;
use App\Domain\Inventory\Models\StockLayer;
use App\Domain\Inventory\Models\StockLayerAllocation;
use App\Domain\Inventory\Models\StockMovement;
use App\Support\Enums\ValuationMethod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Computes COGS by consuming stock layers using FIFO or Weighted Average.
 *
 * On stock-in:  creates a new layer.
 * On stock-out: consumes oldest layers first (FIFO), creates allocations, returns total COGS.
 */
class InventoryValuationService
{
    /**
     * Create a stock layer from an inbound movement.
     * Called automatically after a stock-in operation.
     */
    public function createLayer(StockMovement $movement): StockLayer
    {
        return StockLayer::withoutGlobalScopes()->create([
            'company_id' => $movement->company_id,
            'warehouse_id' => $movement->warehouse_id,
            'product_id' => $movement->product_id,
            'variant_id' => $movement->variant_id,
            'source_movement_id' => $movement->id,
            'quantity_in' => abs($movement->quantity),
            'quantity_remaining' => abs($movement->quantity),
            'unit_cost' => $movement->unit_cost,
            'layer_date' => $movement->movement_date ?? now(),
        ]);
    }

    /**
     * Consume layers FIFO for an outbound movement.
     * Returns the total COGS (in smallest currency unit).
     *
     * @throws RuntimeException If insufficient layers to fulfil the requested quantity
     */
    public function consumeFifo(StockMovement $movement): int
    {
        $quantityNeeded = abs($movement->quantity);
        $totalCogs = 0;

        // Lock and fetch available layers, oldest first
        $layers = StockLayer::withoutGlobalScopes()
            ->where('company_id', $movement->company_id)
            ->where('product_id', $movement->product_id)
            ->where('warehouse_id', $movement->warehouse_id)
            ->when($movement->variant_id, fn ($q, $vid) => $q->where('variant_id', $vid))
            ->where('quantity_remaining', '>', 0)
            ->orderBy('layer_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $remaining = $quantityNeeded;

        foreach ($layers as $layer) {
            if ($remaining <= 0) {
                break;
            }

            $consume = min($remaining, $layer->quantity_remaining);
            $costForThisAllocation = (int) round($consume * $layer->unit_cost);

            // Create the allocation record (COGS audit trail)
            StockLayerAllocation::withoutGlobalScopes()->create([
                'company_id' => $movement->company_id,
                'stock_layer_id' => $layer->id,
                'stock_movement_id' => $movement->id,
                'quantity' => $consume,
                'unit_cost' => $layer->unit_cost,
                'cost_amount' => $costForThisAllocation,
            ]);

            // Reduce the layer's remaining quantity
            StockLayer::withoutGlobalScopes()
                ->where('id', $layer->id)
                ->decrement('quantity_remaining', $consume);

            $totalCogs += $costForThisAllocation;
            $remaining -= $consume;
        }

        if ($remaining > 0) {
            throw new RuntimeException(
                __('Insufficient stock layers for product :id. Needed: :needed, available: :available', [
                    'id' => $movement->product_id,
                    'needed' => $quantityNeeded,
                    'available' => $quantityNeeded - $remaining,
                ])
            );
        }

        // Update the movement's total_cost for COGS tracking
        StockMovement::withoutGlobalScopes()
            ->where('id', $movement->id)
            ->update(['total_cost' => $totalCogs]);

        return $totalCogs;
    }

    /**
     * Compute weighted average cost for a product in a warehouse.
     * Returns the cost per unit (in smallest currency unit).
     */
    public function weightedAverageCost(
        int $companyId,
        int $productId,
        int $warehouseId,
        ?int $variantId = null,
    ): int {
        $layers = StockLayer::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->when($variantId, fn ($q, $vid) => $q->where('variant_id', $vid))
            ->where('quantity_remaining', '>', 0)
            ->get();

        $totalValue = 0;
        $totalQuantity = 0.0;

        foreach ($layers as $layer) {
            $totalValue += (int) round($layer->quantity_remaining * $layer->unit_cost);
            $totalQuantity += $layer->quantity_remaining;
        }

        if ($totalQuantity <= 0) {
            return 0;
        }

        return (int) round($totalValue / $totalQuantity);
    }

    /**
     * Consume layers using weighted average for an outbound movement.
     * Returns the total COGS.
     */
    public function consumeWeightedAverage(StockMovement $movement): int
    {
        $avgCost = $this->weightedAverageCost(
            $movement->company_id,
            $movement->product_id,
            $movement->warehouse_id,
            $movement->variant_id,
        );

        $quantityNeeded = abs($movement->quantity);
        $totalCogs = (int) round($quantityNeeded * $avgCost);

        // Still consume actual layers (FIFO order) for the audit trail,
        // but use weighted average cost for the allocation cost_amount
        $layers = StockLayer::withoutGlobalScopes()
            ->where('company_id', $movement->company_id)
            ->where('product_id', $movement->product_id)
            ->where('warehouse_id', $movement->warehouse_id)
            ->when($movement->variant_id, fn ($q, $vid) => $q->where('variant_id', $vid))
            ->where('quantity_remaining', '>', 0)
            ->orderBy('layer_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $remaining = $quantityNeeded;

        foreach ($layers as $layer) {
            if ($remaining <= 0) {
                break;
            }

            $consume = min($remaining, $layer->quantity_remaining);

            StockLayerAllocation::withoutGlobalScopes()->create([
                'company_id' => $movement->company_id,
                'stock_layer_id' => $layer->id,
                'stock_movement_id' => $movement->id,
                'quantity' => $consume,
                'unit_cost' => $avgCost,
                'cost_amount' => (int) round($consume * $avgCost),
            ]);

            StockLayer::withoutGlobalScopes()
                ->where('id', $layer->id)
                ->decrement('quantity_remaining', $consume);

            $remaining -= $consume;
        }

        if ($remaining > 0) {
            throw new RuntimeException(
                __('Insufficient stock layers for product :id using weighted average.', [
                    'id' => $movement->product_id,
                ])
            );
        }

        StockMovement::withoutGlobalScopes()
            ->where('id', $movement->id)
            ->update(['total_cost' => $totalCogs]);

        return $totalCogs;
    }

    /**
     * Dispatch to the correct valuation engine based on product or company configuration.
     * Product-level override takes precedence over company default.
     */
    public function consume(StockMovement $movement): int
    {
        // Check product-level override first
        $product = \App\Domain\Product\Models\Product::withoutGlobalScopes()->find($movement->product_id);
        $productMethod = $product?->valuation_method;

        if ($productMethod instanceof ValuationMethod) {
            return match ($productMethod) {
                ValuationMethod::FIFO => $this->consumeFifo($movement),
                ValuationMethod::WEIGHTED_AVERAGE => $this->consumeWeightedAverage($movement),
            };
        }

        // Fall back to company default
        $company = Company::withoutGlobalScopes()->find($movement->company_id);
        $raw = $company->valuation_method;
        $method = $raw instanceof ValuationMethod
            ? $raw
            : (ValuationMethod::tryFrom($raw ?? 'fifo') ?? ValuationMethod::FIFO);

        return match ($method) {
            ValuationMethod::FIFO => $this->consumeFifo($movement),
            ValuationMethod::WEIGHTED_AVERAGE => $this->consumeWeightedAverage($movement),
        };
    }

    /**
     * Get the inventory valuation report for a specific product.
     *
     * @return array{layers: Collection, total_quantity: float, total_value: int, avg_cost: int}
     */
    public function valuationReport(
        int $companyId,
        int $productId,
        ?int $warehouseId = null,
    ): array {
        $query = StockLayer::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('quantity_remaining', '>', 0)
            ->when($warehouseId, fn ($q, $wid) => $q->where('warehouse_id', $wid))
            ->orderBy('layer_date')
            ->orderBy('id');

        $layers = $query->get();

        $totalQuantity = $layers->sum('quantity_remaining');
        $totalValue = $layers->sum(fn ($l) => (int) round($l->quantity_remaining * $l->unit_cost));
        $avgCost = $totalQuantity > 0 ? (int) round($totalValue / $totalQuantity) : 0;

        return [
            'layers' => $layers,
            'total_quantity' => $totalQuantity,
            'total_value' => $totalValue,
            'avg_cost' => $avgCost,
        ];
    }
}
