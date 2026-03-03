<?php

namespace App\Domain\SalesOrder\Services;

use App\Support\Enums\SalesOrderStatus;
use App\Models\Company;
use App\Domain\Product\Models\Product;
use App\Domain\SalesOrder\Models\SalesOrder;
use App\Domain\SalesOrder\Models\SalesOrderItem;
use App\Domain\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use App\Domain\Inventory\Services\InventoryService;
use App\Domain\SalesOrder\Actions\ConfirmSalesOrderAction;
use App\Domain\SalesOrder\Actions\CancelSalesOrderAction;
use Illuminate\Support\Facades\DB;

/**
 * Handles all sales order operations.
 */
class SalesOrderService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly SalesOrderRepositoryInterface $salesOrderRepository,
        private readonly ConfirmSalesOrderAction $confirmSalesOrderAction,
        private readonly CancelSalesOrderAction $cancelSalesOrderAction,
    ) {}

    /**
     * Paginated sales order listing with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateOrders(Company $company, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->salesOrderRepository->paginateForCompany($company, $filters, $perPage);
    }

    /**
     * Delete a sales order — only allowed for draft or cancelled orders.
     *
     * @throws \RuntimeException
     */
    public function deleteOrder(SalesOrder $order): void
    {
        if (! in_array($order->status, [SalesOrderStatus::DRAFT, SalesOrderStatus::CANCELLED], true)) {
            throw new \RuntimeException(__('Only draft or cancelled orders can be deleted.'));
        }

        $order->items()->delete();
        $order->delete();
    }

    public function createOrder(Company $company, array $data, array $items, array $customFields = []): SalesOrder
    {
        return DB::transaction(function () use ($company, $data, $items, $customFields): SalesOrder {
            $totals = $this->calculateTotals($items, $company->vat_registered);

            $order = SalesOrder::withoutGlobalScopes()->create(array_merge($data, [
                'company_id' => $company->id,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'],
                'created_by' => auth()->id(),
            ]));

            $this->saveOrderItems($order, $items, $company);

            foreach ($customFields as $key => $value) {
                $order->setCustomField($key, $value);
            }

            return $order;
        });
    }

    public function updateOrder(SalesOrder $order, array $data, array $items): SalesOrder
    {
        return DB::transaction(function () use ($order, $data, $items): SalesOrder {
            $company = Company::withoutGlobalScopes()->findOrFail($order->company_id);
            $totals = $this->calculateTotals($items, $company->vat_registered);

            $order->update(array_merge($data, [
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'],
            ]));

            $order->items()->delete();
            $this->saveOrderItems($order, $items, $company);

            return $order->fresh('items');
        });
    }

    /**
     * Confirm the order and reserve stock for each item.
     */
    public function confirmOrder(SalesOrder $order): void
    {
        $this->confirmSalesOrderAction->execute($order);
    }

    /**
     * Cancel a sales order and release any stock reservations.
     */
    public function cancelOrder(SalesOrder $order): void
    {
        $this->cancelSalesOrderAction->execute($order);
    }

    /**
     * Calculate totals for a set of line items.
     *
     * @param  array<int, array{unit_price: int, quantity: float, discount_percent?: float, tax_rate?: float}>  $items
     * @return array{subtotal: int, discount: int, tax: int, total: int}
     */
    public function calculateTotals(array $items, bool $vatRegistered = false): array
    {
        $subtotal = 0;
        $totalDiscount = 0;
        $totalTax = 0;

        foreach ($items as $item) {
            $unitPrice = (int) ($item['unit_price'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);
            $discountPercent = (float) ($item['discount_percent'] ?? 0);
            $taxRate = (float) ($item['tax_rate'] ?? 0);

            $lineGross = (int) round($unitPrice * $quantity);
            $lineDiscount = (int) round($lineGross * ($discountPercent / 100));
            $lineNet = $lineGross - $lineDiscount;
            $lineTax = $vatRegistered ? (int) round($lineNet * ($taxRate / 100)) : 0;

            $subtotal += $lineGross;
            $totalDiscount += $lineDiscount;
            $totalTax += $lineTax;
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $totalDiscount,
            'tax' => $totalTax,
            'total' => $subtotal - $totalDiscount + $totalTax,
        ];
    }

    /**
     * Save order line items.
     */
    private function saveOrderItems(SalesOrder $order, array $items, Company $company): void
    {
        foreach ($items as $i => $item) {
            $unitPrice = (int) ($item['unit_price'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);
            $discountPercent = (float) ($item['discount_percent'] ?? 0);
            $taxRate = (float) ($item['tax_rate'] ?? 0);

            $lineGross = (int) round($unitPrice * $quantity);
            $lineDiscount = (int) round($lineGross * ($discountPercent / 100));
            $lineNet = $lineGross - $lineDiscount;
            $lineTax = $company->vat_registered ? (int) round($lineNet * ($taxRate / 100)) : 0;

            SalesOrderItem::withoutGlobalScopes()->create([
                'sales_order_id' => $order->id,
                'company_id' => $company->id,
                'product_id' => $item['product_id'] ?? null,
                'variant_id' => $item['variant_id'] ?? null,
                'description' => $item['description'] ?? '',
                'quantity' => $quantity,
                'unit' => $item['unit'] ?? 'pcs',
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'discount_amount' => $lineDiscount,
                'tax_group_id' => $item['tax_group_id'] ?? null,
                'tax_rate' => $taxRate,
                'tax_amount' => $lineTax,
                'line_total' => $lineNet + $lineTax,
                'display_order' => $i,
            ]);
        }
    }
}