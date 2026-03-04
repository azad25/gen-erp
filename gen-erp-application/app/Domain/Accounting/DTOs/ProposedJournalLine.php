<?php

namespace App\Domain\Accounting\DTOs;

/**
 * Single line within a ProposedJournalEntry — exactly one of debit or credit must be > 0.
 */
final readonly class ProposedJournalLine
{
    /**
     * @param  int  $accountId  Chart of Accounts ID
     * @param  int  $debit  Amount in paise (smallest currency unit). 0 if this is a credit line.
     * @param  int  $credit  Amount in paise. 0 if this is a debit line.
     * @param  string  $description  Line-level description
     * @param  string|null  $taxCode  Tax code identifier (e.g. 'VAT_15', 'VAT_EXEMPT')
     * @param  int|null  $taxRate  Tax rate in basis points (1500 = 15.00%)
     * @param  int  $taxBaseAmount  The amount on which the tax was computed, in paise
     * @param  int|null  $branchId  Optional branch dimension for line-level drill-down
     * @param  int|null  $costCenterId  Optional cost center dimension
     * @param  array|null  $dimensions  Additional custom dimensions as key-value pairs
     */
    public function __construct(
        public int $accountId,
        public int $debit,
        public int $credit,
        public string $description = '',
        public ?string $taxCode = null,
        public ?int $taxRate = null,
        public int $taxBaseAmount = 0,
        public ?int $branchId = null,
        public ?int $costCenterId = null,
        public ?array $dimensions = null,
    ) {}
}
