<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Carbon;

/**
 * Generates Mushak 9.1 (Treasury Challan) for VAT deposit to government treasury.
 */
class Mushak91Service
{
    public function __construct(
        private readonly Mushak61ReportService $mushak61,
        private readonly Mushak62ReportService $mushak62,
    ) {}

    /**
     * Generate Mushak 9.1 challan data for a given month.
     *
     * @return array{company_name: string, vat_bin: string|null, period: string, total_output_vat: int, total_input_vat: int, net_vat_payable: int, deposit_date: string|null, challan_data: array<string, mixed>}
     */
    public function generate(Company $company, int $month, int $year): array
    {
        // Calculate output VAT from sales (Mushak 6.2)
        $salesData = $this->mushak62->generate($company, $month, $year);
        $totalOutputVat = collect($salesData)->sum('vat_amount');

        // Calculate input VAT from purchases (Mushak 6.1)
        $purchaseData = $this->mushak61->generate($company, $month, $year);
        $totalInputVat = collect($purchaseData)->sum('vat_amount');

        // Net VAT payable = Output VAT - Input VAT
        $netVatPayable = max(0, $totalOutputVat - $totalInputVat);

        $period = Carbon::createFromDate($year, $month, 1)->format('F Y');

        return [
            'company_name' => $company->name,
            'vat_bin' => $company->vat_bin,
            'period' => $period,
            'total_output_vat' => $totalOutputVat,
            'total_input_vat' => $totalInputVat,
            'net_vat_payable' => $netVatPayable,
            'deposit_date' => null,
            'challan_data' => [
                'treasury_code' => '',
                'bank_name' => '',
                'branch_name' => '',
                'economic_code' => '1/1133/0010/0311',
                'fiscal_year' => $this->fiscalYear($month, $year),
            ],
        ];
    }

    /**
     * Determine the Bangladesh fiscal year (July–June).
     */
    private function fiscalYear(int $month, int $year): string
    {
        if ($month >= 7) {
            return $year.'-'.($year + 1);
        }

        return ($year - 1).'-'.$year;
    }
}
