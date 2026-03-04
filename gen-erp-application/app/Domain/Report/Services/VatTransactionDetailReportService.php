<?php

namespace App\Domain\Report\Services;

use App\Domain\Accounting\Models\JournalEntryLine;
use App\Domain\Auth\Models\Company;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Generates detailed VAT transaction reports showing individual invoice/purchase VAT entries.
 * This provides the detailed breakdown behind the VAT liability summary.
 */
class VatTransactionDetailReportService
{
    /**
     * Generate detailed VAT transaction report for a period.
     *
     * @return array{
     *   period: string,
     *   company: string,
     *   output_vat_transactions: Collection,
     *   input_vat_transactions: Collection,
     *   summary: array{
     *     total_output_vat: int,
     *     total_input_vat: int,
     *     net_vat_position: int,
     *     transaction_count: int
     *   }
     * }
     */
    public function generate(Company $company, Carbon $fromDate, Carbon $toDate): array
    {
        // Get all VAT-tagged journal entry lines for the period
        $vatLines = JournalEntryLine::query()
            ->with(['journalEntry', 'account'])
            ->whereHas('journalEntry', function ($query) use ($company, $fromDate, $toDate) {
                $query->where('company_id', $company->id)
                    ->where('status', 'posted')
                    ->whereBetween('entry_date', [$fromDate->toDateString(), $toDate->toDateString()]);
            })
            ->whereNotNull('tax_code')
            ->where(function ($query) {
                $query->where('tax_code', 'OUTPUT_VAT')
                    ->orWhere('tax_code', 'INPUT_VAT');
            })
            ->orderBy('created_at')
            ->get();

        // Separate output and input VAT transactions
        $outputVatTransactions = $vatLines->where('tax_code', 'OUTPUT_VAT')->map(function ($line) {
            return [
                'date' => $line->journalEntry->entry_date,
                'reference' => $line->journalEntry->reference_type . ' #' . $line->journalEntry->reference_id,
                'description' => $line->description,
                'journal_entry_number' => $line->journalEntry->entry_number,
                'tax_base_amount' => $line->tax_base_amount,
                'tax_rate' => $line->tax_rate,
                'vat_amount' => $line->credit > 0 ? $line->credit : $line->debit,
                'account_code' => $line->account->account_code ?? '',
                'account_name' => $line->account->account_name ?? '',
            ];
        });

        $inputVatTransactions = $vatLines->where('tax_code', 'INPUT_VAT')->map(function ($line) {
            return [
                'date' => $line->journalEntry->entry_date,
                'reference' => $line->journalEntry->reference_type . ' #' . $line->journalEntry->reference_id,
                'description' => $line->description,
                'journal_entry_number' => $line->journalEntry->entry_number,
                'tax_base_amount' => $line->tax_base_amount,
                'tax_rate' => $line->tax_rate,
                'vat_amount' => $line->debit > 0 ? $line->debit : $line->credit,
                'account_code' => $line->account->account_code ?? '',
                'account_name' => $line->account->account_name ?? '',
            ];
        });

        // Calculate totals
        $totalOutputVat = $outputVatTransactions->sum('vat_amount');
        $totalInputVat = $inputVatTransactions->sum('vat_amount');
        $netVatPosition = $totalOutputVat - $totalInputVat;

        return [
            'period' => $fromDate->format('d M Y') . ' - ' . $toDate->format('d M Y'),
            'company' => $company->name,
            'output_vat_transactions' => $outputVatTransactions->values(),
            'input_vat_transactions' => $inputVatTransactions->values(),
            'summary' => [
                'total_output_vat' => $totalOutputVat,
                'total_input_vat' => $totalInputVat,
                'net_vat_position' => $netVatPosition,
                'transaction_count' => $vatLines->count(),
            ],
        ];
    }

    /**
     * Generate VAT transaction detail report for a specific invoice or purchase.
     *
     * @param string $referenceType 'invoice' or 'purchase'
     * @param int $referenceId The ID of the invoice or purchase
     */
    public function getTransactionDetail(Company $company, string $referenceType, int $referenceId): array
    {
        $vatLines = JournalEntryLine::query()
            ->with(['journalEntry', 'account'])
            ->whereHas('journalEntry', function ($query) use ($company, $referenceType, $referenceId) {
                $query->where('company_id', $company->id)
                    ->where('status', 'posted')
                    ->where('reference_type', $referenceType)
                    ->where('reference_id', $referenceId);
            })
            ->whereNotNull('tax_code')
            ->where(function ($query) {
                $query->where('tax_code', 'OUTPUT_VAT')
                    ->orWhere('tax_code', 'INPUT_VAT');
            })
            ->get();

        $transactions = $vatLines->map(function ($line) {
            return [
                'line_id' => $line->id,
                'date' => $line->journalEntry->entry_date,
                'journal_entry_number' => $line->journalEntry->entry_number,
                'account_code' => $line->account->account_code ?? '',
                'account_name' => $line->account->account_name ?? '',
                'description' => $line->description,
                'tax_code' => $line->tax_code,
                'tax_base_amount' => $line->tax_base_amount,
                'tax_rate' => $line->tax_rate,
                'debit' => $line->debit,
                'credit' => $line->credit,
                'vat_amount' => $line->tax_code === 'OUTPUT_VAT' 
                    ? ($line->credit > 0 ? $line->credit : $line->debit)
                    : ($line->debit > 0 ? $line->debit : $line->credit),
            ];
        });

        return [
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'company' => $company->name,
            'transactions' => $transactions->values(),
            'summary' => [
                'total_vat_amount' => $transactions->sum('vat_amount'),
                'total_tax_base' => $transactions->sum('tax_base_amount'),
                'line_count' => $transactions->count(),
            ],
        ];
    }
}