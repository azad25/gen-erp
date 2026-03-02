<?php

namespace App\Services;

use App\Enums\StockAdjustmentStatus;
use App\Enums\StockMovementType;
use App\Enums\StockTransferStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Company;
use App\Models\StockAdjustment;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Central gateway for all stock changes. Never write to stock_levels or stock_movements directly.
 */
class InventoryService
{
    /**
     * Increase stock at a warehouse (purchase receipt, found, production in, etc.).
     */
    public function stockIn(
        int $warehouseId,
        int $productId,
        float $quantity,
        StockMovementType $type,
        ?int $variantId = null,
        ?int $unitCost = null,
        ?string $notes = null,
        ?Model $reference = null,
    ): StockMovement {
        return DB::transaction(function () use ($warehouseId, $productId, $quantity, $type, $variantId, $unitCost, $notes, $reference): StockMovement {
            $level = $this->getOrCreateLevel($warehouseId, $productId, $variantId);
            $quantityBefore = $level->quantity;

            $level->increment('quantity', $quantity);
            $level->refresh();

            return $this->recordMovement(
                $level, $type, $quantity, $quantityBefore, $level->quantity,
                $unitCost, $notes, $reference,
            );
        });
    }

    /**
     * Decrease stock at a warehouse. Throws InsufficientStockException if not enough available.
     */
    public function stockOut(
        int $warehouseId,
        int $productId,
        float $quantity,
        StockMovementType $type,
        ?int $variantId = null,
        ?string $notes = null,
        ?Model $reference = null,
    ): StockMovement {
        return DB::transaction(function () use ($warehouseId, $productId, $quantity, $type, $variantId, $notes, $reference): StockMovement {
            $level = $this->getOrCreateLevel($warehouseId, $productId, $variantId);
            $available = $level->availableQuantity();

            if ($quantity > $available) {
                throw new InsufficientStockException($productId, $quantity, $available, $warehouseId);
            }

            $quantityBefore = $level->quantity;
            $level->decrement('quantity', $quantity);
            $level->refresh();

            return $this->recordMovement(
                $level, $type, $quantity, $quantityBefore, $level->quantity,
                null, $notes, $reference,
            );
        });
    }

    /**
     * Reserve stock for an open order (prevents overselling).
     */
    public function reserve(int $warehouseId, int $productId, float $quantity, ?int $variantId = null): void
    {
        DB::transaction(function () use ($warehouseId, $productId, $quantity, $variantId): void {
            $level = $this->getOrCreateLevel($warehouseId, $productId, $variantId);
            $available = $level->availableQuantity();

            if ($quantity > $available) {
                throw new InsufficientStockException($productId, $quantity, $available, $warehouseId);
            }

            $level->increment('reserved_quantity', $quantity);
        });
    }

    /**
     * Release reservation when order is cancelled.
     */
    public function releaseReservation(int $warehouseId, int $productId, float $quantity, ?int $variantId = null): void
    {
        DB::transaction(function () use ($warehouseId, $productId, $quantity, $variantId): void {
            $level = $this->getOrCreateLevel($warehouseId, $productId, $variantId);
            $release = min($quantity, $level->reserved_quantity);
            $level->decrement('reserved_quantity', $release);
        });
    }

    /**
     * Get stock levels for a product, optionally filtered by warehouse.
     *
     * @return Collection<int, StockLevel>
     */
    public function getStock(int $productId, ?int $warehouseId = null): Collection
    {
        $query = StockLevel::where('product_id', $productId);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get();
    }

    /**
     * Total available quantity across all warehouses.
     */
    public function totalAvailable(int $productId, ?int $variantId = null): float
    {
        $query = StockLevel::where('product_id', $productId);

        if ($variantId !== null) {
            $query->where('variant_id', $variantId);
        }

        $totals = $query->selectRaw('COALESCE(SUM(quantity), 0) as qty, COALESCE(SUM(reserved_quantity), 0) as reserved')
            ->first();

        return (float) $totals->qty - (float) $totals->reserved;
    }

    /**
     * Apply a stock adjustment — creates stock movements for each item.
     */
    public function applyAdjustment(StockAdjustment $adjustment): void
    {
        DB::transaction(function () use ($adjustment): void {
            foreach ($adjustment->items as $item) {
                $diff = $item->adjusted_quantity - $item->current_quantity;

                if ($diff > 0) {
                    $this->stockIn(
                        $item->warehouse_id, $item->product_id, abs($diff),
                        StockMovementType::ADJUSTMENT_IN, $item->variant_id,
                        $item->unit_cost, "Adjustment {$adjustment->reference_number}",
                        $adjustment,
                    );
                } elseif ($diff < 0) {
                    $this->stockOut(
                        $item->warehouse_id, $item->product_id, abs($diff),
                        StockMovementType::ADJUSTMENT_OUT, $item->variant_id,
                        "Adjustment {$adjustment->reference_number}", $adjustment,
                    );
                }
            }

            $adjustment->update(['status' => StockAdjustmentStatus::APPLIED]);
        });
    }

    /**
     * Initiate a stock transfer — moves stock out of source warehouse.
     */
    public function initiateTransfer(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer): void {
            foreach ($transfer->items as $item) {
                $this->stockOut(
                    $transfer->from_warehouse_id, $item->product_id, $item->quantity_sent,
                    StockMovementType::TRANSFER_OUT, $item->variant_id,
                    "Transfer {$transfer->reference_number}", $transfer,
                );
            }

            $transfer->update([
                'status' => StockTransferStatus::IN_TRANSIT,
                'transferred_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Receive a stock transfer — moves stock into destination warehouse.
     *
     * @param  array<int, float>  $receivedQuantities  Keyed by transfer item ID
     */
    public function receiveTransfer(StockTransfer $transfer, array $receivedQuantities): void
    {
        DB::transaction(function () use ($transfer, $receivedQuantities): void {
            foreach ($transfer->items as $item) {
                $received = $receivedQuantities[$item->id] ?? $item->quantity_sent;
                $item->update(['quantity_received' => $received]);

                $this->stockIn(
                    $transfer->to_warehouse_id, $item->product_id, $received,
                    StockMovementType::TRANSFER_IN, $item->variant_id,
                    null, "Transfer {$transfer->reference_number}", $transfer,
                );
            }

            $transfer->update([
                'status' => StockTransferStatus::RECEIVED,
                'received_by' => auth()->id(),
                'received_date' => now()->toDateString(),
            ]);
        });
    }

    /**
     * Set opening stock for a product at a warehouse.
     */
    public function setOpeningStock(int $warehouseId, int $productId, float $quantity, int $unitCost): StockMovement
    {
        return $this->stockIn(
            $warehouseId, $productId, $quantity,
            StockMovementType::OPENING_STOCK, null,
            $unitCost, __('Opening stock'),
        );
    }

    /**
     * Paginated stock movement listing with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateMovements(Company $company, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return StockMovement::query()
            ->where('company_id', $company->id)
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('reference', 'LIKE', "%{$s}%"))
            ->when($filters['movement_type'] ?? null, fn ($q, $t) => $q->where('movement_type', $t))
            ->when($filters['product_id'] ?? null, fn ($q, $id) => $q->where('product_id', $id))
            ->when($filters['warehouse_id'] ?? null, fn ($q, $id) => $q->where('warehouse_id', $id))
            ->with(['product', 'warehouse'])
            ->orderBy('movement_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Stock valuation report data.
     *
     * @return array<int, array{product_id: int, product_name: string, sku: string|null, warehouse: string, quantity: float, avg_cost: int, total_value: int}>
     */
    public function getStockValuation(Company $company, ?int $warehouseId = null): array
    {
        $query = StockLevel::where('stock_levels.company_id', $company->id)
            ->where('stock_levels.quantity', '>', 0)
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'stock_levels.warehouse_id')
            ->select(
                'stock_levels.product_id',
                'products.name as product_name',
                'products.sku',
                'warehouses.name as warehouse',
                'stock_levels.quantity',
            );

        if ($warehouseId !== null) {
            $query->where('stock_levels.warehouse_id', $warehouseId);
        }

        return $query->get()->map(function ($row) {
            // Avg cost from latest movements
            $avgCost = (int) StockMovement::where('product_id', $row->product_id)
                ->whereNotNull('unit_cost')
                ->where('unit_cost', '>', 0)
                ->average('unit_cost');

            return [
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'sku' => $row->sku,
                'warehouse' => $row->warehouse,
                'quantity' => (float) $row->quantity,
                'avg_cost' => $avgCost,
                'total_value' => (int) ($row->quantity * $avgCost),
            ];
        })->all();
    }

    /**
     * Get or create a StockLevel record for the given warehouse/product/variant.
     */
    private function getOrCreateLevel(int $warehouseId, int $productId, ?int $variantId): StockLevel
    {
        return StockLevel::firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'variant_id' => $variantId,
            ],
            [
                'company_id' => StockLevel::withoutGlobalScopes()
                    ->whereHas('warehouse', fn ($q) => $q->where('warehouses.id', $warehouseId))
                    ->value('company_id')
                    ?? \App\Models\Warehouse::withoutGlobalScopes()->findOrFail($warehouseId)->company_id,
                'quantity' => 0,
                'reserved_quantity' => 0,
            ]
        );
    }

    /**
     * Record a stock movement.
     */
    private function recordMovement(
        StockLevel $level,
        StockMovementType $type,
        float $quantity,
        float $quantityBefore,
        float $quantityAfter,
        ?int $unitCost,
        ?string $notes,
        ?Model $reference,
    ): StockMovement {
        return StockMovement::withoutGlobalScopes()->create([
            'company_id' => $level->company_id,
            'warehouse_id' => $level->warehouse_id,
            'product_id' => $level->product_id,
            'variant_id' => $level->variant_id,
            'movement_type' => $type->value,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->getKey(),
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'unit_cost' => $unitCost,
            'notes' => $notes,
            'moved_by' => auth()->id(),
            'movement_date' => now()->toDateString(),
            'created_at' => now(),
        ]);
    }
}
