<?php

namespace App\Listeners;

use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Accounting\Services\PostingService;
use App\Events\CreditNoteApplied;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Automatically creates a reversal journal entry when a credit note is applied.
 * This ensures the ledger reflects the credit note impact correctly.
 */
class CreateCreditNoteReversal implements ShouldQueue
{
    public function __construct(
        private readonly PostingService $postingService,
    ) {}

    public function handle(CreditNoteApplied $event): void
    {
        $creditNote = $event->creditNote;
        $invoice = $event->invoice;

        // Find the original invoice journal entry
        $originalJournal = JournalEntry::withoutGlobalScopes()
            ->where('company_id', $invoice->company_id)
            ->where('reference_type', 'invoice')
            ->where('reference_id', $invoice->id)
            ->where('status', 'posted')
            ->first();

        if ($originalJournal === null) {
            Log::warning('CreateCreditNoteReversal: No posted journal entry found for invoice', [
                'invoice_id' => $invoice->id,
                'credit_note_id' => $creditNote->id,
            ]);
            return;
        }

        // Check if reversal already exists
        if ($originalJournal->reversed_by_id !== null) {
            Log::info('CreateCreditNoteReversal: Journal entry already reversed', [
                'journal_entry_id' => $originalJournal->id,
                'reversed_by_id' => $originalJournal->reversed_by_id,
            ]);
            return;
        }

        try {
            // Create the reversal with credit note reference
            $idempotencyKey = "credit-note-reversal-{$creditNote->id}-{$originalJournal->id}";
            $description = "Reversal for Credit Note {$creditNote->credit_note_number} - {$creditNote->reason}";

            $reversal = $this->postingService->reverse(
                original: $originalJournal,
                idempotencyKey: $idempotencyKey,
                description: $description,
                reversedBy: $creditNote->created_by,
            );

            Log::info('CreateCreditNoteReversal: Successfully created reversal', [
                'original_journal_id' => $originalJournal->id,
                'reversal_journal_id' => $reversal->id,
                'credit_note_id' => $creditNote->id,
            ]);

        } catch (\Exception $e) {
            Log::error('CreateCreditNoteReversal: Failed to create reversal', [
                'original_journal_id' => $originalJournal->id,
                'credit_note_id' => $creditNote->id,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to ensure the queue job fails and can be retried
            throw $e;
        }
    }
}