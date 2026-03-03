<?php

namespace App\Domain\Invoice\Actions;

use App\Domain\Invoice\Models\Invoice;
use App\Domain\Invoice\Models\InvoiceItem;
use App\Domain\SalesOrder\Models\SalesOrder;
use App\Domain\Invoice\Events\InvoiceCreated;
use App\Support\Enums\InvoiceStatus;
use Illuminate\Support\Facades\DB;

/**
 * Convert a sales order into an invoice.
 */
class ConvertOrderToInvoiceAction
{
    public function execute(SalesOrder $order): Invoice
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

            $invoice->load('items');

            // Fire domain event
            InvoiceCreated::dispatch($invoice);

            return $invoice;
        });
    }
}