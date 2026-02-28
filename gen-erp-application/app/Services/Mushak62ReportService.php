<?php

namespace App\Services;

use App\Models\Company;
use App\Models\GoodsReceipt;
use App\Models\Invoice;

/**
 * Generates the monthly Mushak 6.2 (VAT Summary Report) — output tax minus input tax = net VAT payable.
 */
class Mushak62ReportService
{
    /**
     * Generate VAT summary for a given month.
     *
     * @return array{total_output_vat: int, total_input_vat: int, net_vat_payable: int}
     */
    public function generateSummary(Company $company, int $month, int $year): array
    {
        // Output VAT — from sent/paid invoices
        $outputVat = (int) Invoice::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereIn('status', ['sent', 'partial', 'paid'])
            ->whereMonth('invoice_date', $month)
            ->whereYear('invoice_date', $year)
            ->sum('tax_amount');

        // Input VAT — from posted goods receipts
        $inputVat = (int) GoodsReceipt::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', 'posted')
            ->whereMonth('receipt_date', $month)
            ->whereYear('receipt_date', $year)
            ->sum('tax_amount');

        return [
            'total_output_vat' => $outputVat,
            'total_input_vat' => $inputVat,
            'net_vat_payable' => $outputVat - $inputVat,
        ];
    }
}
