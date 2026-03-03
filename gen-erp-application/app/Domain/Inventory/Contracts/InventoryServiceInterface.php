<?php

namespace App\Domain\Inventory\Contracts;

use App\Domain\Auth\Models\Company;
use App\Domain\Inventory\DTOs\CreateWarehouseData;
use App\Domain\Inventory\DTOs\UpdateWarehouseData;
use App\Domain\Inventory\Models\StockAdjustment;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\StockTransfer;
use App\Domain\Inventory\Models\Warehouse;
use App\Support\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface for inventory service operations.
 */
interface InventoryServiceInterface
{
    /**
     * Create a new warehouse.
     */
    public function createWarehouse(CreateWarehouseData $data): Warehouse;

    /**
     * Update a warehouse.
     */
    public function updateWarehouse(Warehouse $warehouse, UpdateWarehouseData $data): Warehouse;

    /**
     * Delete a warehouse.
     */
    public function deleteWarehouse(Warehouse $warehouse): void;

    /**
     * Get warehouses for a company with optional filters.
     */
    public function getWarehouses(Company $company, ?string $search = null, ?bool $isActive = null): \Illuminate\Database\Eloquent\Builder;

    /**
     * Increase stock at a warehouse.
     */
    public function stockIn(
        int $warehouseId,
        int $productId,
        float $quantity,
        StockMovementType $type,
        ?int $variantId = null,
        ?int $unitCost = null,
        ?string $notes = null,
        ?Model $reference = null
    ): StockMovement;

    /**
     * Decrease stock at a warehouse.
     */
    public function stockOut(
        int $warehouseId,
        int $productId,
        float $quantity,
        StockMovementType $type,
        ?int $variantId = null,
        ?string $notes = null,
        ?Model $reference = null
    ): StockMovement;

    /**
     * Reserve stock for future use.
     */
    public function reserve(int $warehouseId, int $productId, float $quantity, ?int $variantId = null): void;

    /**
     * Release reserved stock.
     */
    public function releaseReservation(int $warehouseId, int $productId, float $quantity, ?int $variantId = null): void;

    /**
     * Get stock levels for a product.
     */
    public function getStock(int $productId, ?int $warehouseId = null): Collection;

    /**
     * Get total available stock for a product.
     */
    public function totalAvailable(int $productId, ?int $variantId = null): float;

    /**
     * Apply stock adjustment.
     */
    public function applyAdjustment(StockAdjustment $adjustment): void;

    /**
     * Initiate stock transfer.
     */
    public function initiateTransfer(StockTransfer $transfer): void;

    /**
     * Receive stock transfer.
     */
    public function receiveTransfer(StockTransfer $transfer, array $receivedQuantities): void;

    /**
     * Set opening stock.
     */
    public function setOpeningStock(int $warehouseId, int $productId, float $quantity, int $unitCost): StockMovement;

    /**
     * Paginate stock movements.
     */
    public function paginateMovements(Company $company, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Get stock valuation.
     */
    public function getStockValuation(Company $company, ?int $warehouseId = null): array;
}