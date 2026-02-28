<?php

namespace App\Services;

use App\Enums\TaxType;
use App\Models\TaxGroup;

/**
 * Calculates tax amounts for Bangladesh tax system: compound SD + VAT, simple VAT, AIT.
 */
class TaxCalculationService
{
    /**
     * Calculate tax for a given amount using one or more tax groups.
     *
     * Compound logic (BD standard):
     * 1. Calculate SD on base amount
     * 2. Calculate VAT on (base + SD) — SD-inclusive
     * 3. AIT is calculated on base amount independently
     *
     * @param  int  $amountPaise  Base amount in paise (smallest currency unit)
     * @param  TaxGroup  ...$groups  Tax groups to apply
     * @return TaxResult
     */
    public function calculate(int $amountPaise, TaxGroup ...$groups): TaxResult
    {
        $sdAmount = 0;
        $vatAmount = 0;
        $aitAmount = 0;
        $breakdown = [];

        // Sort: SD first, then VAT, then AIT (compound order matters)
        $sorted = collect($groups)->sortBy(function (TaxGroup $g): int {
            return match ($g->type) {
                TaxType::SD => 1,
                TaxType::VAT => 2,
                TaxType::AIT => 3,
                default => 4,
            };
        });

        $sdInclusiveBase = $amountPaise;

        foreach ($sorted as $group) {
            $rate = $group->rate;

            if ($rate <= 0) {
                $breakdown[] = [
                    'tax_group_id' => $group->id,
                    'name' => $group->name,
                    'type' => $group->type->value,
                    'rate' => $rate,
                    'taxable_amount' => $amountPaise,
                    'tax_amount' => 0,
                ];

                continue;
            }

            $taxAmount = 0;
            $taxableAmount = $amountPaise;

            switch ($group->type) {
                case TaxType::SD:
                    $taxAmount = (int) round($amountPaise * $rate / 100);
                    $sdAmount += $taxAmount;
                    $sdInclusiveBase = $amountPaise + $sdAmount;
                    $taxableAmount = $amountPaise;
                    break;

                case TaxType::VAT:
                    // VAT on SD-inclusive amount when compound
                    if ($group->is_compound && $sdAmount > 0) {
                        $taxableAmount = $sdInclusiveBase;
                    }
                    $taxAmount = (int) round($taxableAmount * $rate / 100);
                    $vatAmount += $taxAmount;
                    break;

                case TaxType::AIT:
                    $taxAmount = (int) round($amountPaise * $rate / 100);
                    $aitAmount += $taxAmount;
                    $taxableAmount = $amountPaise;
                    break;
            }

            $breakdown[] = [
                'tax_group_id' => $group->id,
                'name' => $group->name,
                'type' => $group->type->value,
                'rate' => $rate,
                'taxable_amount' => $taxableAmount,
                'tax_amount' => $taxAmount,
            ];
        }

        $totalTax = $sdAmount + $vatAmount + $aitAmount;

        return new TaxResult(
            subtotal: $amountPaise,
            sdAmount: $sdAmount,
            vatAmount: $vatAmount,
            aitAmount: $aitAmount,
            totalTax: $totalTax,
            grandTotal: $amountPaise + $totalTax,
            breakdown: $breakdown,
        );
    }

    /**
     * Calculate TDS (Tax Deducted at Source) for supplier payment.
     *
     * @param  int  $paymentAmountPaise  Payment amount in paise
     * @param  int  $tdsRateBasisPoints  TDS rate in basis points (e.g., 500 = 5%)
     * @return array{tds_amount: int, net_payment: int, tds_rate_percent: float}
     */
    public function calculateTds(int $paymentAmountPaise, int $tdsRateBasisPoints): array
    {
        $tdsRate = $tdsRateBasisPoints / 10000;
        $tdsAmount = (int) round($paymentAmountPaise * $tdsRate);

        return [
            'tds_amount' => $tdsAmount,
            'net_payment' => $paymentAmountPaise - $tdsAmount,
            'tds_rate_percent' => (float) ($tdsRateBasisPoints / 100),
        ];
    }
}
