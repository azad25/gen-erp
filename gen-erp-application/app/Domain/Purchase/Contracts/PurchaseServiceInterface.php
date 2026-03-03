<?php

namespace App\Domain\Purchase\Contracts;

use App\Domain\Auth\Models\Company;
use App\Domain\Purchase\Models\GoodsReceipt;
use App\Domain\Purchase\Models\PurchaseOrder;

/**
 * Interface for purchase service operations.
 */
interface PurchaseServiceInterface
{
    /**
     * Paginate purchase orders with filters.
     */
    public function paginateOrders(Company $company, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Delete a purchase order.
     */
    public function deleteOrder(PurchaseOrder $order): void;

    /**
     * Create a new purchase order.
     */
    public function createOrder(Company $company, array $data, array $items, array $customFields = []): PurchaseOrder;

    /**
     * Update a purchase order.
     */
    public function updateOrder(PurchaseOrder $order, array $data, array $items): PurchaseOrder;

    /**
     * Send a purchase order to supplier.
     */
    public function sendOrder(PurchaseOrder $order): void;

    /**
     * Cancel a purchase order.
     */
    public function cancelOrder(PurchaseOrder $order): void;

    /**
     * Create goods receipt from purchase order.
     */
    public function createReceipt(PurchaseOrder $order, array $items): GoodsReceipt;

    /**
     * Create direct goods receipt.
     */
    public function createDirectReceipt(Company $company, array $data, array $items): GoodsReceipt;

    /**
     * Post goods receipt to inventory.
     */
    public function postReceipt(GoodsReceipt $receipt): void;

    /**
     * Calculate totals for purchase order items.
     */
    public function calculateTotals(array $items): array;

    /**
     * Calculate TDS/VDS for purchase order.
     */
    public function calculateTdsVds(PurchaseOrder $order): array;
}