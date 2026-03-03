<?php

namespace App\Domain\Sales\Services;

use App\Domain\Auth\Models\Company;
use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Models\CustomerTransaction;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\Product\Models\Product;
use App\Domain\SalesOrder\Models\SalesOrder;
use App\Exceptions\InsufficientStockException;
use App\Services\CompanyContext;
use App\Domain\Inventory\Services\InventoryService;
use App\Domain\System\Services\SequenceService;
use App\Support\Enums\InvoiceStatus;
use App\Support\Enums\SalesOrderStatus;
use App\Support\Enums\StockMovementType;
use Illuminate\Support\Facades\DB;

/**
 * Manages sales operations - orders, invoices, stock deduction.
 */
class SalesService
{
    public function __construct(
        private SequenceService $sequenceService,
        private InventoryService $inventoryService
    ) {}

    /**
     * Create a new sales order.
     */
    public function createOrder(Company $company, array $data, array $items): SalesOrder
    {
        return DB::transaction(function () use ($company, $data, $items) {
            $data['company_id'] = $company->id;
            $data['reference_number'] = $this->sequenceService->next('sales_order', $company->id);
            $data['status'] = SalesOrderStatus::DRAFT;

            $totals = $this->calculateTotals($items, $company->vat_registered ?? false);
            $data = array_merge($data, $totals);

            $order = SalesOrder::create($data);

            foreach ($items as $item) {
                $lineTotal = $item['unit_price'] * $item['quantity'];
                $lineDiscount = (int) round($lineTotal * (($item['discount_percent'] ?? 0) / 100));
                $netAmount = $lineTotal - $lineDiscount;
                $lineTax = ($company->vat_registered ?? false) ? (int) round($netAmount * (($item['tax_rate'] ?? 0) / 100)) : 0;
                $finalTotal = $netAmount + $lineTax;
                
                $order->items()->create([
                    'company_id' => $company->id,
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'unit' => $item['unit'] ?? 'pcs',
                    'discount_percent' => $item['discount_percent'] ?? 0,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'line_total' => $finalTotal,
                ]);
            }

            return $order->load('items');
        });
    }

    /**
     * Confirm an order and reserve stock.
     */
    public function confirmOrder(SalesOrder $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if ($item->product_id && $item->product->track_inventory) {
                    $this->inventoryService->reserve(
                        $order->warehouse_id,
                        $item->product_id,
                        $item->quantity
                    );
                }
            }

            $order->update(['status' => SalesOrderStatus::CONFIRMED]);
        });
    }

    /**
     * Cancel an order and release reservations.
     */
    public function cancelOrder(SalesOrder $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if ($item->product_id && $item->product->track_inventory) {
                    $this->inventoryService->releaseReservation(
                        $order->warehouse_id,
                        $item->product_id,
                        $item->quantity
                    );
                }
            }

            $order->update(['status' => SalesOrderStatus::CANCELLED]);
        });
    }

    /**
     * Convert a sales order to an invoice.
     */
    public function convertToInvoice(SalesOrder $order): Invoice
    {
        return DB::transaction(function () use ($order) {
            $invoiceData = [
                'company_id' => $order->company_id,
                'customer_id' => $order->customer_id,
                'warehouse_id' => $order->warehouse_id,
                'sales_order_id' => $order->id,
                'invoice_number' => $this->sequenceService->next('invoice', $order->company_id),
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'status' => InvoiceStatus::DRAFT,
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'tax_amount' => $order->tax_amount,
                'total_amount' => $order->total_amount,
            ];

            $invoice = Invoice::create($invoiceData);

            foreach ($order->items as $orderItem) {
                $lineTotal = $orderItem->unit_price * $orderItem->quantity;
                $lineDiscount = (int) round($lineTotal * ($orderItem->discount_percent / 100));
                $netAmount = $lineTotal - $lineDiscount;
                $lineTax = ($order->company->vat_registered ?? false) ? (int) round($netAmount * ($orderItem->tax_rate / 100)) : 0;
                $finalTotal = $netAmount + $lineTax;
                
                $invoice->items()->create([
                    'company_id' => $order->company_id,
                    'product_id' => $orderItem->product_id,
                    'description' => $orderItem->description,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'unit' => $orderItem->unit,
                    'discount_percent' => $orderItem->discount_percent,
                    'tax_rate' => $orderItem->tax_rate,
                    'line_total' => $finalTotal,
                ]);
            }

            return $invoice->load('items');
        });
    }

    /**
     * Create a direct invoice.
     */
    public function createInvoice(Company $company, array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($company, $data, $items) {
            $data['company_id'] = $company->id;
            $data['invoice_number'] = $this->sequenceService->next('invoice', $company->id);
            $data['invoice_date'] = $data['invoice_date'] ?? now()->toDateString();
            $data['due_date'] = $data['due_date'] ?? now()->addDays(30)->toDateString();
            $data['status'] = InvoiceStatus::DRAFT;

            $totals = $this->calculateTotals($items, $company->vat_registered ?? false);
            $data = array_merge($data, $totals);

            $invoice = Invoice::create($data);

            foreach ($items as $item) {
                $lineTotal = $item['unit_price'] * $item['quantity'];
                $lineDiscount = (int) round($lineTotal * (($item['discount_percent'] ?? 0) / 100));
                $netAmount = $lineTotal - $lineDiscount;
                $lineTax = ($company->vat_registered ?? false) ? (int) round($netAmount * (($item['tax_rate'] ?? 0) / 100)) : 0;
                $finalTotal = $netAmount + $lineTax;
                
                $invoice->items()->create([
                    'company_id' => $company->id,
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'unit' => $item['unit'] ?? 'pcs',
                    'discount_percent' => $item['discount_percent'] ?? 0,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'line_total' => $finalTotal,
                ]);
            }

            return $invoice->load('items');
        });
    }

    /**
     * Send an invoice - deduct stock and record transaction.
     */
    public function sendInvoice(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            // Deduct stock for tracked products
            foreach ($invoice->items as $item) {
                if ($item->product_id && $item->product->track_inventory) {
                    $this->inventoryService->stockOut(
                        $invoice->warehouse_id,
                        $item->product_id,
                        $item->quantity,
                        StockMovementType::SALE,
                        $item->variant_id, // variant_id
                        "Invoice {$invoice->invoice_number}", // notes
                        $invoice // reference
                    );
                }
            }

            // Record customer transaction
            CustomerTransaction::create([
                'company_id' => $invoice->company_id,
                'customer_id' => $invoice->customer_id,
                'type' => 'invoice',
                'amount' => $invoice->total_amount,
                'balance_after' => $invoice->customer->currentBalance() + $invoice->total_amount,
                'description' => "Invoice {$invoice->invoice_number}",
                'transaction_date' => $invoice->invoice_date,
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
            ]);

            $invoice->update([
                'status' => InvoiceStatus::SENT,
                'stock_deducted' => true,
            ]);
        });
    }

    /**
     * Cancel an invoice - reverse stock and record negative transaction.
     */
    public function cancelInvoice(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            // Restore stock if it was deducted
            if ($invoice->stock_deducted) {
                foreach ($invoice->items as $item) {
                    if ($item->product_id && $item->product->track_inventory) {
                        $this->inventoryService->stockIn(
                            $invoice->warehouse_id,
                            $item->product_id,
                            $item->quantity,
                            StockMovementType::SALE_RETURN,
                            $item->variant_id, // variant_id
                            null, // unit_cost
                            "Invoice {$invoice->invoice_number} cancelled", // notes
                            $invoice // reference
                        );
                    }
                }
            }

            // Record negative customer transaction
            CustomerTransaction::create([
                'company_id' => $invoice->company_id,
                'customer_id' => $invoice->customer_id,
                'type' => 'cancellation',
                'amount' => -$invoice->total_amount,
                'balance_after' => $invoice->customer->currentBalance() - $invoice->total_amount,
                'description' => "Invoice {$invoice->invoice_number} cancelled",
                'transaction_date' => now()->toDateString(),
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
            ]);

            $invoice->update(['status' => InvoiceStatus::CANCELLED]);
        });
    }

    /**
     * Calculate totals for line items.
     */
    public function calculateTotals(array $items, bool $vatRegistered = false): array
    {
        $subtotal = 0;
        $discount = 0;
        $tax = 0;

        foreach ($items as $item) {
            $lineTotal = $item['unit_price'] * $item['quantity'];
            $lineDiscount = (int) round($lineTotal * (($item['discount_percent'] ?? 0) / 100));
            $netAmount = $lineTotal - $lineDiscount;
            $lineTax = $vatRegistered ? (int) round($netAmount * (($item['tax_rate'] ?? 0) / 100)) : 0;

            $subtotal += $lineTotal;
            $discount += $lineDiscount;
            $tax += $lineTax;
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'discount_amount' => $discount,
            'tax' => $tax,
            'tax_amount' => $tax,
            'total' => $subtotal - $discount + $tax,
            'total_amount' => $subtotal - $discount + $tax,
        ];
    }
}