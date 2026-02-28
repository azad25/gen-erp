<?php

namespace App\Services;

use App\Models\Company;
use App\Models\GoodsReceipt;

/**
 * Generates the monthly Mushak 6.1 (Purchase Register) required by NBR for VAT-registered companies.
 */
class Mushak61ReportService
{
    /**
     * Generate Mushak 6.1 report data for a given month.
     *
     * @return array<int, array{supplier_name: string, vat_bin: string|null, invoice_date: string, invoice_number: string, taxable_value: int, vat_rate: float, vat_amount: int, total: int}>
     */
    public function generate(Company $company, int $month, int $year): array
    {
        $receipts = GoodsReceipt::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', 'posted')
            ->whereMonth('receipt_date', $month)
            ->whereYear('receipt_date', $year)
            ->whereNotNull('supplier_id')
            ->with(['supplier', 'items'])
            ->get();

        $rows = [];

        foreach ($receipts as $receipt) {
            $supplier = $receipt->supplier;

            // Only include VAT-registered suppliers
            if ($supplier === null || $supplier->vat_bin === null || $supplier->vat_bin === '') {
                continue;
            }

            $taxableValue = $receipt->subtotal;
            $vatAmount = $receipt->tax_amount;

            $rows[] = [
                'supplier_name' => $supplier->name,
                'vat_bin' => $supplier->vat_bin,
                'invoice_date' => $receipt->supplier_invoice_date?->format('d M Y') ?? $receipt->receipt_date->format('d M Y'),
                'invoice_number' => $receipt->supplier_invoice_number ?? $receipt->receipt_number,
                'taxable_value' => $taxableValue,
                'vat_rate' => $receipt->items->avg('tax_rate') ?? 0,
                'vat_amount' => $vatAmount,
                'total' => $receipt->total_amount,
            ];
        }

        return $rows;
    }
}
