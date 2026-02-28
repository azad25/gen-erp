<?php

namespace App\Services;

/**
 * Data transfer object for tax calculation results.
 */
readonly class TaxResult
{
    /**
     * @param  int  $subtotal  Base amount before tax (paise)
     * @param  int  $sdAmount  Supplementary Duty amount (paise)
     * @param  int  $vatAmount  VAT amount (paise)
     * @param  int  $aitAmount  Advance Income Tax amount (paise)
     * @param  int  $totalTax  Total tax = SD + VAT + AIT (paise)
     * @param  int  $grandTotal  Subtotal + totalTax (paise)
     * @param  array<int, array{tax_group_id: int, name: string, type: string, rate: float, taxable_amount: int, tax_amount: int}>  $breakdown
     */
    public function __construct(
        public int $subtotal,
        public int $sdAmount,
        public int $vatAmount,
        public int $aitAmount,
        public int $totalTax,
        public int $grandTotal,
        public array $breakdown = [],
    ) {}

    /**
     * Format grand total for BDT display.
     */
    public function formattedGrandTotal(): string
    {
        return '৳'.number_format($this->grandTotal / 100, 2);
    }

    /**
     * Format total tax for BDT display.
     */
    public function formattedTotalTax(): string
    {
        return '৳'.number_format($this->totalTax / 100, 2);
    }
}
