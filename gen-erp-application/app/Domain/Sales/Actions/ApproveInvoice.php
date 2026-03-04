<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Accounting\DTOs\ProposedJournalEntry;
use App\Domain\Accounting\DTOs\ProposedJournalLine;
use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Services\PostingService;
use App\Domain\Inventory\Services\InventoryService;
use App\Domain\Inventory\Services\InventoryValuationService;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\Product\Models\Product;
use App\Support\Enums\AccountSubType;
use App\Support\Enums\InvoiceStatus;
use App\Support\Enums\JournalCode;
use App\Support\Enums\StockMovementType;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Atomically approve an invoice: deduct stock, compute COGS, post balanced journal entry, mark as sent.
 *
 * The journal entry includes both revenue AND COGS lines:
 *   DR: Accounts Receivable   (total_amount)
 *   CR: Sales Revenue          (subtotal)
 *   CR: Output VAT Payable     (tax_amount) — if VAT
 *   DR: Cost of Goods Sold     (computed COGS)
 *   CR: Inventory              (computed COGS)
 */
class ApproveInvoice
{
    public function __construct(
        private readonly PostingService $postingService,
        private readonly InventoryService $inventoryService,
        private readonly InventoryValuationService $valuationService,
    ) {}

    /**
     * Execute the approval flow for the given invoice.
     *
     * @throws RuntimeException If the invoice is not in draft status or system accounts are missing
     */
    public function execute(Invoice $invoice, ?int $approvedBy = null): Invoice
    {
        if ($invoice->status !== InvoiceStatus::DRAFT) {
            throw new RuntimeException(
                __('Only draft invoices can be approved. Current status: :status', [
                    'status' => $invoice->status->label(),
                ])
            );
        }

        return DB::transaction(function () use ($invoice, $approvedBy): Invoice {
            // Lock the invoice row to prevent concurrent approval
            $invoice = Invoice::withoutGlobalScopes()
                ->where('id', $invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Double-check status after acquiring lock
            if ($invoice->status !== InvoiceStatus::DRAFT) {
                throw new RuntimeException(__('Invoice was already approved by another process.'));
            }

            // ── 1. Deduct stock and compute COGS from layers ────────
            $totalCogs = $this->deductStockAndComputeCogs($invoice);

            // ── 2. Build and post the journal entry (revenue + COGS) ────
            $idempotencyKey = "invoice_{$invoice->id}_approve";
            $proposed = $this->buildProposedJournal($invoice, $idempotencyKey, $approvedBy, $totalCogs);
            $this->postingService->post($proposed, $approvedBy);

            // ── 3. Transition invoice to SENT status ────────
            Invoice::withoutGlobalScopes()
                ->where('id', $invoice->id)
                ->update([
                    'status' => InvoiceStatus::SENT->value,
                    'stock_deducted' => true,
                ]);

            return $invoice->fresh();
        }, attempts: 5);
    }

    /**
     * Deduct inventory for each tracked product and compute total COGS
     * by consuming stock layers via InventoryValuationService.
     *
     * @return int Total COGS in smallest currency unit (paise)
     */
    private function deductStockAndComputeCogs(Invoice $invoice): int
    {
        if ($invoice->stock_deducted) {
            return 0;
        }

        $invoice->load('items');
        $totalCogs = 0;

        foreach ($invoice->items as $item) {
            if ($item->product_id === null) {
                continue;
            }

            $product = Product::withoutGlobalScopes()->find($item->product_id);
            if ($product === null || ! $product->track_inventory) {
                continue;
            }

            // Step 1: Record the stock-out movement
            $movement = $this->inventoryService->stockOut(
                warehouseId: $invoice->warehouse_id,
                productId: $item->product_id,
                quantity: (float) $item->quantity,
                type: StockMovementType::SALE,
                variantId: $item->variant_id,
                notes: "Invoice {$invoice->invoice_number}",
                reference: $invoice,
            );

            // Step 2: Consume stock layers (FIFO/WAC) to compute COGS
            try {
                $cogs = $this->valuationService->consume($movement);
                $totalCogs += $cogs;
            } catch (RuntimeException) {
                // If no layers exist (legacy data), COGS = 0 for this item
                // The movement is still recorded for stock tracking
            }
        }

        return $totalCogs;
    }

    /**
     * Build the ProposedJournalEntry for the invoice posting.
     *
     * Revenue lines:
     *   DR: Accounts Receivable  (total_amount)
     *   CR: Sales Revenue        (subtotal)
     *   CR: Output VAT Payable   (tax_amount) — only if tax > 0
     *
     * COGS lines (if tracked inventory):
     *   DR: Cost of Goods Sold   (totalCogs)
     *   CR: Inventory            (totalCogs)
     */
    private function buildProposedJournal(
        Invoice $invoice,
        string $idempotencyKey,
        ?int $createdBy,
        int $totalCogs = 0,
    ): ProposedJournalEntry {
        $receivable = $this->findSystemAccount($invoice->company_id, AccountSubType::RECEIVABLE);
        $revenue = $this->findSystemAccount($invoice->company_id, AccountSubType::REVENUE);

        $lines = [
            new ProposedJournalLine(
                accountId: $receivable->id,
                debit: $invoice->total_amount,
                credit: 0,
                description: __('Accounts Receivable'),
            ),
            new ProposedJournalLine(
                accountId: $revenue->id,
                debit: 0,
                credit: $invoice->subtotal,
                description: __('Sales Revenue'),
            ),
        ];

        // ── VAT separation: Tax goes to Output VAT Payable, not Revenue ──
        if ($invoice->tax_amount > 0) {
            $vatPayable = $this->findSystemAccount(
                $invoice->company_id,
                AccountSubType::CURRENT_LIABILITY,
                '2002',
            );

            $lines[] = new ProposedJournalLine(
                accountId: $vatPayable->id,
                debit: 0,
                credit: $invoice->tax_amount,
                description: __('Output VAT Payable'),
                taxCode: 'OUTPUT_VAT',
                taxRate: $this->computeEffectiveTaxRate($invoice),
                taxBaseAmount: $invoice->subtotal,
            );
        }

        // ── COGS lines: recognise cost of goods sold against inventory ──
        if ($totalCogs > 0) {
            $cogsAccount = $this->findSystemAccount($invoice->company_id, AccountSubType::COGS);
            $inventoryAccount = $this->findSystemAccount($invoice->company_id, AccountSubType::INVENTORY);

            $lines[] = new ProposedJournalLine(
                accountId: $cogsAccount->id,
                debit: $totalCogs,
                credit: 0,
                description: __('Cost of Goods Sold'),
            );

            $lines[] = new ProposedJournalLine(
                accountId: $inventoryAccount->id,
                debit: 0,
                credit: $totalCogs,
                description: __('Inventory reduction for COGS'),
            );
        }

        return new ProposedJournalEntry(
            companyId: $invoice->company_id,
            idempotencyKey: $idempotencyKey,
            journalCode: JournalCode::SALES,
            entryDate: ($invoice->invoice_date ?? now())->toDateString(),
            description: __('Invoice :number', ['number' => $invoice->invoice_number]),
            referenceType: 'invoice',
            referenceId: $invoice->id,
            lines: $lines,
            branchId: $invoice->branch_id,
            createdBy: $createdBy,
        );
    }

    /**
     * Compute effective tax rate in basis points from invoice amounts.
     */
    private function computeEffectiveTaxRate(Invoice $invoice): int
    {
        if ($invoice->subtotal === 0) {
            return 0;
        }

        return (int) round(($invoice->tax_amount / $invoice->subtotal) * 10000);
    }

    private function findSystemAccount(int $companyId, AccountSubType $subType, ?string $code = null): Account
    {
        $query = Account::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_system', true)
            ->where('sub_type', $subType);

        if ($code !== null) {
            $query->where('code', $code);
        }

        $account = $query->first();

        if ($account === null) {
            throw new RuntimeException(__('System account not found: :type', ['type' => $subType->label()]));
        }

        return $account;
    }
}
