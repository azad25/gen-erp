<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\SalesOrderStatus;
use App\Enums\StockMovementType;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates all sales order and invoice operations.
 */
class SalesService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly ContactService $contactService,
    ) {}

    /**
     * Paginated sales order listing with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateOrders(Company $company, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return SalesOrder::query()
            ->where('company_id', $company->id)
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(function ($q) use ($s): void {
                $q->where('order_number', 'LIKE', "%{$s}%")
                    ->orWhere('reference', 'LIKE', "%{$s}%");
            }))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['customer_id'] ?? null, fn ($q, $id) => $q->where('customer_id', $id))
            ->with(['customer', 'items.product'])
            ->orderBy('order_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Paginated invoice listing with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateInvoices(Company $company, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Invoice::query()
            ->where('company_id', $company->id)
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(function ($q) use ($s): void {
                $q->where('invoice_number', 'LIKE', "%{$s}%")
                    ->orWhere('reference', 'LIKE', "%{$s}%");
            }))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['customer_id'] ?? null, fn ($q, $id) => $q->where('customer_id', $id))
            ->with(['customer', 'items.product'])
            ->orderBy('invoice_date', 'desc')
            ->paginate($perPage);
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
        DB::transaction(function () use ($order): void {
            foreach ($order->items as $item) {
                if ($item->product_id === null) {
                    continue;
                }

                $product = Product::withoutGlobalScopes()->find($item->product_id);
                if ($product === null || ! $product->track_inventory) {
                    continue;
                }

                $this->inventoryService->reserve(
                    $order->warehouse_id,
                    $item->product_id,
                    (float) $item->quantity,
                    $item->variant_id,
                );
            }

            $order->update(['status' => SalesOrderStatus::CONFIRMED]);
        });
    }

    /**
     * Cancel a sales order and release any stock reservations.
     */
    public function cancelOrder(SalesOrder $order): void
    {
        DB::transaction(function () use ($order): void {
            if ($order->status === SalesOrderStatus::CONFIRMED || $order->status === SalesOrderStatus::PROCESSING) {
                foreach ($order->items as $item) {
                    if ($item->product_id === null) {
                        continue;
                    }

                    $product = Product::withoutGlobalScopes()->find($item->product_id);
                    if ($product === null || ! $product->track_inventory) {
                        continue;
                    }

                    $this->inventoryService->releaseReservation(
                        $order->warehouse_id,
                        $item->product_id,
                        (float) $item->quantity,
                        $item->variant_id,
                    );
                }
            }

            $order->update(['status' => SalesOrderStatus::CANCELLED]);
        });
    }

    /**
     * Convert a sales order into an invoice.
     */
    public function convertToInvoice(SalesOrder $order): Invoice
    {
        return DB::transaction(function () use ($order): Invoice {
            $customer = $order->customer;
            $creditDays = $customer?->credit_days ?? 30;

            $invoice = Invoice::withoutGlobalScopes()->create([
                'company_id' => $order->company_id,
                'sales_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'warehouse_id' => $order->warehouse_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays($creditDays)->toDateString(),
                'status' => InvoiceStatus::DRAFT,
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'tax_amount' => $order->tax_amount,
                'shipping_amount' => $order->shipping_amount ?? 0,
                'total_amount' => $order->total_amount,
                'notes' => $order->notes,
                'terms_conditions' => $order->terms_conditions,
                'created_by' => auth()->id(),
            ]);

            foreach ($order->items as $i => $item) {
                InvoiceItem::withoutGlobalScopes()->create([
                    'invoice_id' => $invoice->id,
                    'company_id' => $order->company_id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'discount_percent' => $item->discount_percent,
                    'discount_amount' => $item->discount_amount,
                    'tax_group_id' => $item->tax_group_id,
                    'tax_rate' => $item->tax_rate,
                    'tax_amount' => $item->tax_amount,
                    'line_total' => $item->line_total,
                    'display_order' => $i,
                ]);
            }

            return $invoice->load('items');
        });
    }

    /**
     * Create a direct invoice (not from a sales order).
     */
    public function createInvoice(Company $company, array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($company, $data, $items): Invoice {
            $totals = $this->calculateTotals($items, $company->vat_registered);

            $creditDays = 30;
            if (isset($data['customer_id'])) {
                $customer = \App\Models\Customer::withoutGlobalScopes()->find($data['customer_id']);
                $creditDays = $customer?->credit_days ?? 30;
            }

            $invoiceDate = $data['invoice_date'] ?? now()->toDateString();
            $dueDate = $data['due_date'] ?? now()->addDays($creditDays)->toDateString();

            $invoice = Invoice::withoutGlobalScopes()->create(array_merge($data, [
                'company_id' => $company->id,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'],
                'created_by' => auth()->id(),
            ]));

            $this->saveInvoiceItems($invoice, $items, $company);

            return $invoice;
        });
    }

    /**
     * Update a draft invoice.
     */
    public function updateInvoice(Invoice $invoice, array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $items): Invoice {
            $company = Company::withoutGlobalScopes()->findOrFail($invoice->company_id);
            $totals = $this->calculateTotals($items, $company->vat_registered);

            $invoice->update(array_merge($data, [
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'],
            ]));

            $invoice->items()->delete();
            $this->saveInvoiceItems($invoice, $items, $company);

            return $invoice->fresh('items');
        });
    }

    /**
     * Send an invoice — deduct stock, record customer transaction, update status.
     */
    public function sendInvoice(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            $invoice->load('items');
            $invoice->deductStock();

            if ($invoice->customer_id !== null) {
                $customer = \App\Models\Customer::withoutGlobalScopes()->findOrFail($invoice->customer_id);

                $this->contactService->recordCustomerTransaction(
                    $customer,
                    'invoice',
                    $invoice->total_amount,
                    "Invoice {$invoice->invoice_number}",
                    $invoice,
                );
            }

            // Release reservations if this invoice came from a confirmed order
            if ($invoice->sales_order_id !== null) {
                $order = SalesOrder::withoutGlobalScopes()->find($invoice->sales_order_id);
                if ($order !== null && $order->status === SalesOrderStatus::CONFIRMED) {
                    foreach ($order->items as $item) {
                        if ($item->product_id === null) {
                            continue;
                        }

                        $product = Product::withoutGlobalScopes()->find($item->product_id);
                        if ($product === null || ! $product->track_inventory) {
                            continue;
                        }

                        $this->inventoryService->releaseReservation(
                            $order->warehouse_id,
                            $item->product_id,
                            (float) $item->quantity,
                            $item->variant_id,
                        );
                    }
                }
            }

            $invoice->update(['status' => InvoiceStatus::SENT]);
        });
    }

    /**
     * Cancel an invoice — reverse stock if already deducted.
     */
    public function cancelInvoice(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            if ($invoice->stock_deducted) {
                foreach ($invoice->items as $item) {
                    if ($item->product_id === null) {
                        continue;
                    }

                    $product = Product::withoutGlobalScopes()->find($item->product_id);
                    if ($product === null || ! $product->track_inventory) {
                        continue;
                    }

                    $this->inventoryService->stockIn(
                        $invoice->warehouse_id,
                        $item->product_id,
                        (float) $item->quantity,
                        StockMovementType::SALE_RETURN,
                        $item->variant_id,
                        null,
                        "Invoice {$invoice->invoice_number} cancelled",
                        $invoice,
                    );
                }
            }

            if ($invoice->customer_id !== null) {
                $customer = \App\Models\Customer::withoutGlobalScopes()->findOrFail($invoice->customer_id);

                $this->contactService->recordCustomerTransaction(
                    $customer,
                    'credit_note',
                    -$invoice->total_amount,
                    "Invoice {$invoice->invoice_number} cancelled",
                    $invoice,
                );
            }

            $invoice->update([
                'status' => InvoiceStatus::CANCELLED,
                'stock_deducted' => false,
            ]);
        });
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

    /**
     * Save invoice line items.
     */
    private function saveInvoiceItems(Invoice $invoice, array $items, Company $company): void
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

            InvoiceItem::withoutGlobalScopes()->create([
                'invoice_id' => $invoice->id,
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
