<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Accounting\Services\PostingService;
use App\Domain\Inventory\Services\InventoryService;
use App\Domain\Invoice\Models\Invoice;
use App\Support\Enums\InvoiceStatus;
use App\Support\Enums\JournalEntryStatus;
use App\Support\Enums\StockMovementType;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Cancel a posted invoice: restore stock, reverse the journal entry, mark invoice cancelled.
 *
 * This ensures the accounting books remain balanced after cancellation:
 *   - Original journal reversed (DR ↔ CR swap via PostingService::reverse)
 *   - Stock quantities restored (stock-in to undo the sale deduction)
 *   - Invoice marked CANCELLED (no further actions possible)
 */
class CancelInvoiceWithReversal
{
    public function __construct(
        private readonly PostingService $postingService,
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * Execute the cancellation flow for the given invoice.
     *
     * @throws RuntimeException If the invoice is not in a cancellable status
     */
    public function execute(Invoice $invoice, ?int $cancelledBy = null, ?string $reason = null): Invoice
    {
        if (! in_array($invoice->status, [InvoiceStatus::SENT, InvoiceStatus::OVERDUE], true)) {
            throw new RuntimeException(
                __('Only sent or overdue invoices can be cancelled. Current status: :status', [
                    'status' => $invoice->status->label(),
                ])
            );
        }

        return DB::transaction(function () use ($invoice, $cancelledBy, $reason): Invoice {
            // Lock the invoice row
            $invoice = Invoice::withoutGlobalScopes()
                ->where('id', $invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Double-check status after acquiring lock
            if (! in_array($invoice->status, [InvoiceStatus::SENT, InvoiceStatus::OVERDUE], true)) {
                throw new RuntimeException(__('Invoice was already cancelled by another process.'));
            }

            // ── 1. Reverse the journal entry ────────
            $this->reverseJournal($invoice, $cancelledBy, $reason);

            // ── 2. Restore stock for tracked products ────────
            $this->restoreStock($invoice);

            // ── 3. Mark invoice as cancelled ────────
            Invoice::withoutGlobalScopes()
                ->where('id', $invoice->id)
                ->update([
                    'status' => InvoiceStatus::CANCELLED->value,
                    'stock_deducted' => false,
                ]);

            return $invoice->fresh();
        }, attempts: 5);
    }

    /**
     * Reverse the journal entry created during invoice approval.
     */
    private function reverseJournal(Invoice $invoice, ?int $cancelledBy, ?string $reason): void
    {
        $originalEntry = JournalEntry::withoutGlobalScopes()
            ->where('company_id', $invoice->company_id)
            ->where('reference_type', 'invoice')
            ->where('reference_id', $invoice->id)
            ->where('status', JournalEntryStatus::POSTED)
            ->first();

        if ($originalEntry === null) {
            return; // No journal to reverse (e.g. non-inventory invoice)
        }

        $this->postingService->reverse(
            original: $originalEntry,
            idempotencyKey: "invoice_{$invoice->id}_cancel_reversal",
            description: $reason ?? __('Cancellation of Invoice :number', ['number' => $invoice->invoice_number]),
            reversedBy: $cancelledBy,
        );
    }

    /**
     * Restore stock for each tracked product (stock-in to undo the sale deduction).
     */
    private function restoreStock(Invoice $invoice): void
    {
        if (! $invoice->stock_deducted) {
            return;
        }

        $invoice->load('items');

        foreach ($invoice->items as $item) {
            if ($item->product_id === null) {
                continue;
            }

            $product = \App\Domain\Product\Models\Product::withoutGlobalScopes()->find($item->product_id);
            if ($product === null || ! $product->track_inventory) {
                continue;
            }

            $this->inventoryService->stockIn(
                warehouseId: $invoice->warehouse_id,
                productId: $item->product_id,
                quantity: (float) $item->quantity,
                type: StockMovementType::SALE_RETURN,
                unitCost: null,
                variantId: $item->variant_id,
                notes: "Cancelled Invoice {$invoice->invoice_number}",
                reference: $invoice,
            );
        }
    }
}
