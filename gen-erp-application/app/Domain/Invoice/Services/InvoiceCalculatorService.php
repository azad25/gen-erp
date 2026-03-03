<?php

namespace App\Domain\Invoice\Services;

use App\DataTransferObjects\Invoice\CreateInvoiceItemData;

class InvoiceCalculatorService
{
    /**
     * Calculate totals for invoice items.
     *
     * @param CreateInvoiceItemData[] $items
     * @return array{subtotal: int, discount: int, tax: int, total: int}
     */
    public function calculateTotals(array $items): array
    {
        $subtotal = 0;
        $totalDiscount = 0;
        $totalTax = 0;

        foreach ($items as $item) {
            $lineGross = (int) round($item->unitPrice * $item->quantity);
            $lineDiscount = (int) round($lineGross * ($item->discountPercent / 100));
            $lineNet = $lineGross - $lineDiscount;
            $lineTax = (int) round($lineNet * ($item->taxRate / 100));

            $subtotal += $lineGross;
            $totalDiscount += $lineDiscount;
            $totalTax += $lineTax;
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $totalDiscount,
            'tax' => $totalTax,
            'total' => $subtotal - $totalDiscount + $totalTax,
        ];
    }
}