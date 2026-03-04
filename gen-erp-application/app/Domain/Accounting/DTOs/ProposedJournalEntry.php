<?php

namespace App\Domain\Accounting\DTOs;

use App\Support\Enums\JournalCode;

/**
 * Immutable DTO representing a proposed journal entry before it is persisted.
 * Built by domain actions, consumed by PostingService.
 */
final readonly class ProposedJournalEntry
{
    /**
     * @param  int  $companyId
     * @param  string  $idempotencyKey  Globally unique key to prevent duplicate posting
     * @param  JournalCode  $journalCode  Classification of the journal (sales, purchase, bank, etc.)
     * @param  string  $entryDate  ISO date string
     * @param  string  $description  Human-readable description
     * @param  string  $referenceType  Morph type (e.g. 'invoice', 'customer_payment')
     * @param  int  $referenceId  Related document ID
     * @param  array<int, ProposedJournalLine>  $lines  Debit/credit lines
     * @param  string  $currency  ISO currency code
     * @param  int|null  $branchId  Optional branch dimension
     * @param  int|null  $createdBy  User who initiated the action
     */
    public function __construct(
        public int $companyId,
        public string $idempotencyKey,
        public JournalCode $journalCode,
        public string $entryDate,
        public string $description,
        public string $referenceType,
        public int $referenceId,
        public array $lines,
        public string $currency = 'BDT',
        public ?int $branchId = null,
        public ?int $createdBy = null,
    ) {}

    /**
     * Validate that the proposed entry is balanced (sum of debits == sum of credits).
     */
    public function isBalanced(): bool
    {
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($this->lines as $line) {
            $totalDebit += $line->debit;
            $totalCredit += $line->credit;
        }

        return $totalDebit === $totalCredit && $totalDebit > 0;
    }

    public function totalDebits(): int
    {
        return array_sum(array_map(fn (ProposedJournalLine $l) => $l->debit, $this->lines));
    }

    public function totalCredits(): int
    {
        return array_sum(array_map(fn (ProposedJournalLine $l) => $l->credit, $this->lines));
    }
}
