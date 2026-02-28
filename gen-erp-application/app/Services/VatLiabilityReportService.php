<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Carbon;

/**
 * Computes monthly VAT liability summary combining output VAT, input VAT credit, and net payable.
 */
class VatLiabilityReportService
{
    public function __construct(
        private readonly Mushak61ReportService $mushak61,
        private readonly Mushak62ReportService $mushak62,
        private readonly Mushak66Service $mushak66,
    ) {}

    /**
     * Generate a comprehensive VAT liability report for a given month.
     *
     * @return array{period: string, company: string, vat_bin: string|null, output_vat: array{total_sales: int, total_vat: int, invoice_count: int}, input_vat: array{total_purchases: int, total_vat: int, receipt_count: int}, adjustments: array{credit_notes: int, credit_note_vat: int}, summary: array{gross_output_vat: int, less_input_vat: int, less_adjustments: int, net_vat_payable: int}}
     */
    public function generate(Company $company, int $month, int $year): array
    {
        $salesData = $this->mushak62->generate($company, $month, $year);
        $purchaseData = $this->mushak61->generate($company, $month, $year);
        $creditNoteData = $this->mushak66->generate($company, $month, $year);

        $totalOutputVat = collect($salesData)->sum('vat_amount');
        $totalSales = collect($salesData)->sum('taxable_value');

        $totalInputVat = collect($purchaseData)->sum('vat_amount');
        $totalPurchases = collect($purchaseData)->sum('taxable_value');

        $creditNoteVat = collect($creditNoteData)->sum('vat_amount');

        $netVatPayable = max(0, $totalOutputVat - $totalInputVat - $creditNoteVat);

        $period = Carbon::createFromDate($year, $month, 1)->format('F Y');

        return [
            'period' => $period,
            'company' => $company->name,
            'vat_bin' => $company->vat_bin,
            'output_vat' => [
                'total_sales' => $totalSales,
                'total_vat' => $totalOutputVat,
                'invoice_count' => count($salesData),
            ],
            'input_vat' => [
                'total_purchases' => $totalPurchases,
                'total_vat' => $totalInputVat,
                'receipt_count' => count($purchaseData),
            ],
            'adjustments' => [
                'credit_notes' => count($creditNoteData),
                'credit_note_vat' => $creditNoteVat,
            ],
            'summary' => [
                'gross_output_vat' => $totalOutputVat,
                'less_input_vat' => $totalInputVat,
                'less_adjustments' => $creditNoteVat,
                'net_vat_payable' => $netVatPayable,
            ],
        ];
    }
}
