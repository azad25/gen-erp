<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\DTOs\ProposedJournalEntry;
use App\Domain\Accounting\DTOs\ProposedJournalLine;
use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Accounting\Models\JournalEntryLine;
use App\Domain\Auth\Models\Company;
use App\Support\Enums\JournalEntryStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * Idempotent, atomic posting engine for double-entry journal entries.
 *
 * Rules enforced:
 * 1. Debits == Credits (balanced entry)
 * 2. Idempotency — duplicate idempotency_key returns existing entry
 * 3. Lock-date validation — cannot post before company lock_date
 * 4. Transaction isolation — uses DB::transaction with deadlock retry
 */
class PostingService
{
    /**
     * Post a ProposedJournalEntry atomically.
     *
     * If an entry with the same idempotency_key already exists, that entry
     * is returned immediately without creating a duplicate — this is the
     * core idempotency guarantee.
     *
     * @throws InvalidArgumentException  If the entry is not balanced
     * @throws RuntimeException          If lock-date is violated or system account missing
     */
    public function post(ProposedJournalEntry $proposed, ?int $postedBy = null): JournalEntry
    {
        // ── 1. Validate balance ────────────────────────
        if (! $proposed->isBalanced()) {
            throw new InvalidArgumentException(
                __('Proposed journal entry is not balanced. Debits: :d, Credits: :c', [
                    'd' => $proposed->totalDebits(),
                    'c' => $proposed->totalCredits(),
                ])
            );
        }

        // ── 2. Idempotency check (before transaction to save a round-trip) ──
        $existing = JournalEntry::withoutGlobalScopes()
            ->where('idempotency_key', $proposed->idempotencyKey)
            ->first();

        if ($existing !== null) {
            Log::info('PostingService: idempotent duplicate detected', [
                'idempotency_key' => $proposed->idempotencyKey,
                'journal_entry_id' => $existing->id,
            ]);

            return $existing->load('lines');
        }

        // ── 3. Lock-date validation ────────────────────
        $company = Company::withoutGlobalScopes()->find($proposed->companyId);

        if ($company === null) {
            throw new RuntimeException(__('Company not found: :id', ['id' => $proposed->companyId]));
        }

        if ($company->lock_date !== null && $proposed->entryDate <= $company->lock_date->toDateString()) {
            throw new RuntimeException(
                __('Cannot post journal entry on or before the lock date (:date).', [
                    'date' => $company->lock_date->format('d M Y'),
                ])
            );
        }

        // ── 4. Atomic posting with deadlock retry ──────
        return DB::transaction(function () use ($proposed, $postedBy): JournalEntry {
            // Re-check idempotency inside transaction (race-condition guard)
            $existing = JournalEntry::withoutGlobalScopes()
                ->where('idempotency_key', $proposed->idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing->load('lines');
            }

            // Generate entry number
            $seq = JournalEntry::withoutGlobalScopes()
                ->where('company_id', $proposed->companyId)
                ->lockForUpdate()
                ->count() + 1;

            $entryNumber = 'JE-' . now()->format('Ymd') . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

            // Create the header
            $entry = new JournalEntry();
            $entry->company_id = $proposed->companyId;
            $entry->idempotency_key = $proposed->idempotencyKey;
            $entry->entry_number = $entryNumber;
            $entry->journal_code = $proposed->journalCode->value;
            $entry->entry_date = $proposed->entryDate;
            $entry->reference_type = $proposed->referenceType;
            $entry->reference_id = $proposed->referenceId;
            $entry->description = $proposed->description;
            $entry->currency = $proposed->currency;
            $entry->status = JournalEntryStatus::POSTED;
            $entry->is_system = true;
            $entry->created_by = $proposed->createdBy ?? $postedBy;
            $entry->posted_by = $postedBy;
            $entry->posted_at = now();
            $entry->branch_id = $proposed->branchId;
            $entry->save();

            // Create lines
            $lineNo = 1;
            foreach ($proposed->lines as $line) {
                /** @var ProposedJournalLine $line */
                JournalEntryLine::withoutGlobalScopes()->create([
                    'company_id' => $proposed->companyId,
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line->accountId,
                    'line_no' => $lineNo++,
                    'description' => $line->description,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                    'tax_code' => $line->taxCode,
                    'tax_rate' => $line->taxRate,
                    'tax_base_amount' => $line->taxBaseAmount,
                    'branch_id' => $line->branchId ?? $proposed->branchId,
                    'cost_center_id' => $line->costCenterId,
                    'dimensions' => $line->dimensions,
                ]);
            }

            Log::info('PostingService: journal entry posted', [
                'journal_entry_id' => $entry->id,
                'idempotency_key' => $proposed->idempotencyKey,
                'total' => $proposed->totalDebits(),
            ]);

            return $entry->load('lines');
        }, attempts: 5);
    }

    /**
     * Create a reverse journal entry for an existing posted entry.
     * Used for credit notes and corrections.
     */
    public function reverse(
        JournalEntry $original,
        string $idempotencyKey,
        string $description,
        ?int $reversedBy = null,
    ): JournalEntry {
        if ($original->status !== JournalEntryStatus::POSTED) {
            throw new RuntimeException(__('Only posted entries can be reversed.'));
        }

        $original->load('lines');

        $reversedLines = [];
        foreach ($original->lines as $line) {
            $reversedLines[] = new ProposedJournalLine(
                accountId: $line->account_id,
                debit: $line->credit,    // swap debit ↔ credit
                credit: $line->debit,
                description: 'Reversal: ' . $line->description,
                taxCode: $line->tax_code,
                taxRate: $line->tax_rate,
                taxBaseAmount: $line->tax_base_amount,
                branchId: $line->branch_id,
                costCenterId: $line->cost_center_id,
                dimensions: $line->dimensions,
            );
        }

        $proposed = new ProposedJournalEntry(
            companyId: $original->company_id,
            idempotencyKey: $idempotencyKey,
            journalCode: $original->journal_code instanceof \App\Support\Enums\JournalCode
                ? $original->journal_code
                : \App\Support\Enums\JournalCode::from($original->journal_code ?? 'general'),
            entryDate: now()->toDateString(),
            description: $description,
            referenceType: $original->reference_type ?? 'journal_entry',
            referenceId: $original->reference_id ?? $original->id,
            lines: $reversedLines,
            currency: $original->currency ?? 'BDT',
            branchId: $original->branch_id,
            createdBy: $reversedBy,
        );

        $reversal = $this->post($proposed, $reversedBy);

        // Link original ↔ reversal bi-directionally
        // Use direct DB update to bypass the "posted entries cannot be modified" guard
        JournalEntry::withoutGlobalScopes()
            ->where('id', $original->id)
            ->update(['reversed_by_id' => $reversal->id]);

        JournalEntry::withoutGlobalScopes()
            ->where('id', $reversal->id)
            ->update(['reversal_of_id' => $original->id]);

        return $reversal->fresh('lines');
    }
}
