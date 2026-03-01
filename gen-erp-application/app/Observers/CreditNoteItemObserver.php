<?php

namespace App\Observers;

use App\Models\CreditNoteItem;

class CreditNoteItemObserver
{
    /**
     * Handle the CreditNoteItem "creating" event.
     */
    public function creating(CreditNoteItem $creditNoteItem): void
    {
        $this->calculateTotals($creditNoteItem);
    }

    /**
     * Handle the CreditNoteItem "updating" event.
     */
    public function updating(CreditNoteItem $creditNoteItem): void
    {
        $this->calculateTotals($creditNoteItem);
    }

    /**
     * Calculate tax_amount and line_total for the credit note item.
     */
    private function calculateTotals(CreditNoteItem $creditNoteItem): void
    {
        $quantity = $creditNoteItem->quantity ?? 0;
        $unitPrice = $creditNoteItem->unit_price ?? 0;
        $taxRate = $creditNoteItem->tax_rate ?? 0;

        $subtotal = $quantity * $unitPrice;
        $taxAmount = ($subtotal * $taxRate) / 100;
        
        $creditNoteItem->tax_amount = (int) round($taxAmount);
        $creditNoteItem->line_total = (int) round($subtotal + $taxAmount);
    }
}
