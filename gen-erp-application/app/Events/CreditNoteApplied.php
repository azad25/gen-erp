<?php

namespace App\Events;

use App\Domain\Customer\Models\CreditNote;
use App\Domain\Invoice\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a credit note is applied to an invoice.
 * This triggers the automatic reversal of the original invoice journal entry.
 */
class CreditNoteApplied
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CreditNote $creditNote,
        public readonly Invoice $invoice,
    ) {}
}