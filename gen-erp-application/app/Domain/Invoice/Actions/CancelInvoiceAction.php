<?php

namespace App\Domain\Invoice\Actions;

use App\Domain\Invoice\Models\Invoice;
use App\Domain\Invoice\Events\InvoiceCancelled;
use App\Domain\Customer\Services\ContactService;
use App\Domain\Inventory\Services\InventoryService;
use App\Support\Enums\InvoiceStatus;
use App\Support\Enums\StockMovementType;
use App\Domain\Product\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Cancel an invoice — reverse stock if already deducted.
 */
class CancelInvoiceAction
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly ContactService $contactService,
    ) {}

    public function execute(Invoice $invoice): void
    {
        throw_if(
            $invoice->status === InvoiceStatus::CANCELLED,
            new \InvalidArgumentException('Invoice is already cancelled.')
        );

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
                $customer = \App\Domain\Customer\Models\Customer::withoutGlobalScopes()->findOrFail($invoice->customer_id);

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

            // Fire domain event
            InvoiceCancelled::dispatch($invoice);
        });
    }
}