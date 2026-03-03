<?php

namespace App\Domain\Invoice\Actions;

use App\Domain\Invoice\Models\Invoice;
use App\Domain\Invoice\Events\InvoiceSent;
use App\Domain\Customer\Services\ContactService;
use App\Domain\Inventory\Services\InventoryService;
use App\Support\Enums\InvoiceStatus;
use App\Support\Enums\SalesOrderStatus;
use App\Domain\SalesOrder\Models\SalesOrder;
use App\Domain\Product\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Send an invoice — deduct stock, record customer transaction, update status.
 */
class SendInvoiceAction
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly ContactService $contactService,
    ) {}

    public function execute(Invoice $invoice): void
    {
        throw_if(
            $invoice->status !== InvoiceStatus::DRAFT,
            new \InvalidArgumentException('Only draft invoices can be sent.')
        );

        DB::transaction(function () use ($invoice): void {
            $invoice->load('items');
            $invoice->deductStock();

            if ($invoice->customer_id !== null) {
                $customer = \App\Domain\Customer\Models\Customer::withoutGlobalScopes()->findOrFail($invoice->customer_id);

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

            // Fire domain event
            InvoiceSent::dispatch($invoice);
        });
    }
}