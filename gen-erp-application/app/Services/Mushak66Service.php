<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CreditNote;

/**
 * Generates Mushak 6.6 (Credit Note / Return Register) required by NBR for VAT adjustments.
 */
class Mushak66Service
{
    /**
     * Generate Mushak 6.6 report data for a given month.
     *
     * @return array<int, array{credit_note_number: string, date: string, customer_name: string, vat_bin: string|null, original_invoice: string, reason: string, taxable_value: int, vat_rate: float, vat_amount: int, total: int}>
     */
    public function generate(Company $company, int $month, int $year): array
    {
        $creditNotes = CreditNote::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereMonth('credit_note_date', $month)
            ->whereYear('credit_note_date', $year)
            ->with(['customer', 'items', 'invoice'])
            ->get();

        $rows = [];

        foreach ($creditNotes as $cn) {
            $customer = $cn->customer;

            $rows[] = [
                'credit_note_number' => $cn->credit_note_number,
                'date' => $cn->credit_note_date->format('d M Y'),
                'customer_name' => $customer?->name ?? __('Unknown'),
                'vat_bin' => $customer?->vat_bin,
                'original_invoice' => $cn->invoice?->invoice_number ?? '',
                'reason' => $cn->reason ?? '',
                'taxable_value' => $cn->subtotal,
                'vat_rate' => $cn->items->avg('tax_rate') ?? 0,
                'vat_amount' => $cn->tax_amount,
                'total' => $cn->total_amount,
            ];
        }

        return $rows;
    }
}
