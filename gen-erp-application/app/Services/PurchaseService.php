<?php

namespace App\Services;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\Company;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Orchestrates all purchase order and goods receipt operations.
 */
class PurchaseService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly ContactService $contactService,
    ) {}

    /**
     * Paginated purchase order listing with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateOrders(Company $company, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return PurchaseOrder::query()
            ->where('company_id', $company->id)
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(function ($q) use ($s): void {
                $q->where('order_number', 'LIKE', "%{$s}%")
                    ->orWhere('reference', 'LIKE', "%{$s}%");
            }))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['supplier_id'] ?? null, fn ($q, $id) => $q->where('supplier_id', $id))
            ->with(['supplier', 'items.product'])
            ->orderBy('order_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Delete a purchase order — only allowed for draft or cancelled orders.
     *
     * @throws RuntimeException
     */
    public function deleteOrder(PurchaseOrder $order): void
    {
        if (! in_array($order->status, [PurchaseOrderStatus::DRAFT, PurchaseOrderStatus::CANCELLED], true)) {
            throw new RuntimeException(__('Only draft or cancelled orders can be deleted.'));
        }

        $order->items()->delete();
        $order->delete();
    }

    public function createOrder(Company $company, array $data, array $items, array $customFields = []): PurchaseOrder
    {
        return DB::transaction(function () use ($company, $data, $items, $customFields): PurchaseOrder {
            $totals = $this->calculateTotals($items);

            $order = PurchaseOrder::withoutGlobalScopes()->create(array_merge($data, [
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

    public function updateOrder(PurchaseOrder $order, array $data, array $items): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $data, $items): PurchaseOrder {
            $company = Company::withoutGlobalScopes()->findOrFail($order->company_id);
            $totals = $this->calculateTotals($items);

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
     * Mark PO as sent to supplier.
     */
    public function sendOrder(PurchaseOrder $order): void
    {
        $order->update(['status' => PurchaseOrderStatus::SENT]);
    }

    /**
     * Cancel a PO — only if no posted receipts exist.
     */
    public function cancelOrder(PurchaseOrder $order): void
    {
        $hasPosted = $order->goodsReceipts()
            ->where('status', GoodsReceiptStatus::POSTED)
            ->exists();

        if ($hasPosted) {
            throw new RuntimeException(__('Cannot cancel a purchase order with posted receipts.'));
        }

        $order->update(['status' => PurchaseOrderStatus::CANCELLED]);
    }

    /**
     * Create a goods receipt from a purchase order (supports partial receipt).
     *
     * @param  array<int, array{purchase_order_item_id: int, quantity_received: float}>  $items
     */
    public function createReceipt(PurchaseOrder $order, array $items): GoodsReceipt
    {
        return DB::transaction(function () use ($order, $items): GoodsReceipt {
            $subtotal = 0;
            $taxTotal = 0;

            $receipt = GoodsReceipt::withoutGlobalScopes()->create([
                'company_id' => $order->company_id,
                'purchase_order_id' => $order->id,
                'supplier_id' => $order->supplier_id,
                'warehouse_id' => $order->warehouse_id,
                'receipt_date' => now()->toDateString(),
                'status' => GoodsReceiptStatus::DRAFT,
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $poItem = PurchaseOrderItem::withoutGlobalScopes()->findOrFail($item['purchase_order_item_id']);

                $lineGross = (int) round($poItem->unit_cost * (float) $item['quantity_received']);
                $lineTax = (int) round($lineGross * ($poItem->tax_rate / 100));
                $lineTotal = $lineGross + $lineTax;

                GoodsReceiptItem::withoutGlobalScopes()->create([
                    'goods_receipt_id' => $receipt->id,
                    'company_id' => $order->company_id,
                    'purchase_order_item_id' => $poItem->id,
                    'product_id' => $poItem->product_id,
                    'variant_id' => $poItem->variant_id,
                    'description' => $poItem->description,
                    'quantity_received' => $item['quantity_received'],
                    'unit' => $poItem->unit,
                    'unit_cost' => $poItem->unit_cost,
                    'tax_rate' => $poItem->tax_rate,
                    'tax_amount' => $lineTax,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineGross;
                $taxTotal += $lineTax;
            }

            $receipt->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total_amount' => $subtotal + $taxTotal,
            ]);

            return $receipt->load('items');
        });
    }

    /**
     * Create a direct receipt without a linked PO.
     */
    public function createDirectReceipt(Company $company, array $data, array $items): GoodsReceipt
    {
        return DB::transaction(function () use ($company, $data, $items): GoodsReceipt {
            $subtotal = 0;
            $taxTotal = 0;

            $receipt = GoodsReceipt::withoutGlobalScopes()->create(array_merge($data, [
                'company_id' => $company->id,
                'status' => GoodsReceiptStatus::DRAFT,
                'created_by' => auth()->id(),
            ]));

            foreach ($items as $item) {
                $unitCost = (int) ($item['unit_cost'] ?? 0);
                $qty = (float) ($item['quantity_received'] ?? 0);
                $taxRate = (float) ($item['tax_rate'] ?? 0);

                $lineGross = (int) round($unitCost * $qty);
                $lineTax = (int) round($lineGross * ($taxRate / 100));
                $lineTotal = $lineGross + $lineTax;

                GoodsReceiptItem::withoutGlobalScopes()->create([
                    'goods_receipt_id' => $receipt->id,
                    'company_id' => $company->id,
                    'product_id' => $item['product_id'] ?? null,
                    'variant_id' => $item['variant_id'] ?? null,
                    'description' => $item['description'] ?? '',
                    'quantity_received' => $qty,
                    'unit' => $item['unit'] ?? 'pcs',
                    'unit_cost' => $unitCost,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineGross;
                $taxTotal += $lineTax;
            }

            $receipt->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total_amount' => $subtotal + $taxTotal,
            ]);

            return $receipt->load('items');
        });
    }

    /**
     * Post a goods receipt — atomically adds stock and records supplier transaction.
     */
    public function postReceipt(GoodsReceipt $receipt): void
    {
        DB::transaction(function () use ($receipt): void {
            $receipt->load('items');
            $receipt->addStock();

            // Update PO item received quantities and PO status
            if ($receipt->purchase_order_id !== null) {
                $order = PurchaseOrder::withoutGlobalScopes()->find($receipt->purchase_order_id);

                if ($order !== null) {
                    foreach ($receipt->items as $grItem) {
                        if ($grItem->purchase_order_item_id !== null) {
                            $poItem = PurchaseOrderItem::withoutGlobalScopes()->find($grItem->purchase_order_item_id);
                            if ($poItem !== null) {
                                $poItem->update([
                                    'quantity_received' => $poItem->quantity_received + $grItem->quantity_received,
                                ]);
                            }
                        }
                    }

                    $order->refresh();
                    $order->load('items');

                    $newStatus = $order->isFullyReceived()
                        ? PurchaseOrderStatus::RECEIVED
                        : PurchaseOrderStatus::PARTIAL;

                    $order->update([
                        'status' => $newStatus,
                        'amount_received_value' => $order->amount_received_value + $receipt->total_amount,
                    ]);
                }
            }

            // Record supplier transaction (we owe the supplier)
            if ($receipt->supplier_id !== null) {
                $supplier = Supplier::withoutGlobalScopes()->findOrFail($receipt->supplier_id);

                $this->contactService->recordSupplierTransaction(
                    $supplier,
                    'goods_receipt',
                    $receipt->total_amount,
                    "GRN {$receipt->receipt_number}",
                    $receipt,
                );
            }

            $receipt->update(['status' => GoodsReceiptStatus::POSTED]);
        });
    }

    /**
     * Calculate totals for purchase order line items.
     *
     * @param  array<int, array{unit_cost: int, quantity_ordered?: float, quantity?: float, discount_percent?: float, tax_rate?: float}>  $items
     * @return array{subtotal: int, discount: int, tax: int, total: int}
     */
    public function calculateTotals(array $items): array
    {
        $subtotal = 0;
        $totalDiscount = 0;
        $totalTax = 0;

        foreach ($items as $item) {
            $unitCost = (int) ($item['unit_cost'] ?? 0);
            $quantity = (float) ($item['quantity_ordered'] ?? $item['quantity'] ?? 0);
            $discountPercent = (float) ($item['discount_percent'] ?? 0);
            $taxRate = (float) ($item['tax_rate'] ?? 0);

            $lineGross = (int) round($unitCost * $quantity);
            $lineDiscount = (int) round($lineGross * ($discountPercent / 100));
            $lineNet = $lineGross - $lineDiscount;
            $lineTax = (int) round($lineNet * ($taxRate / 100));

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
     * Calculate TDS/VDS from the supplier's rates.
     *
     * @return array{net: int, tds_amount: int, vds_amount: int}
     */
    public function calculateTdsVds(PurchaseOrder $order): array
    {
        if ($order->supplier_id === null) {
            return ['net' => $order->total_amount, 'tds_amount' => 0, 'vds_amount' => 0];
        }

        $supplier = Supplier::withoutGlobalScopes()->find($order->supplier_id);
        if ($supplier === null) {
            return ['net' => $order->total_amount, 'tds_amount' => 0, 'vds_amount' => 0];
        }

        return $supplier->netPaymentAmount($order->total_amount);
    }

    /**
     * Save purchase order line items.
     */
    private function saveOrderItems(PurchaseOrder $order, array $items, Company $company): void
    {
        foreach ($items as $i => $item) {
            $unitCost = (int) ($item['unit_cost'] ?? 0);
            $quantity = (float) ($item['quantity_ordered'] ?? $item['quantity'] ?? 0);
            $discountPercent = (float) ($item['discount_percent'] ?? 0);
            $taxRate = (float) ($item['tax_rate'] ?? 0);

            $lineGross = (int) round($unitCost * $quantity);
            $lineDiscount = (int) round($lineGross * ($discountPercent / 100));
            $lineNet = $lineGross - $lineDiscount;
            $lineTax = (int) round($lineNet * ($taxRate / 100));

            PurchaseOrderItem::withoutGlobalScopes()->create([
                'purchase_order_id' => $order->id,
                'company_id' => $company->id,
                'product_id' => $item['product_id'] ?? null,
                'variant_id' => $item['variant_id'] ?? null,
                'description' => $item['description'] ?? '',
                'quantity_ordered' => $quantity,
                'unit' => $item['unit'] ?? 'pcs',
                'unit_cost' => $unitCost,
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
