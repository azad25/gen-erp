<?php

namespace App\Domain\Report\Services;

use App\Domain\Auth\Models\Company;
use App\Domain\Customer\Models\Customer;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\Purchase\Models\Supplier;
use App\Domain\Purchase\Models\GoodsReceipt;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Generates Accounts Receivable and Accounts Payable aging reports.
 * Shows outstanding balances categorized by age buckets.
 */
class AgingReportService
{
    /**
     * Generate Accounts Receivable aging report.
     *
     * @return array{
     *   as_of_date: string,
     *   company: string,
     *   customers: array,
     *   summary: array{
     *     total_outstanding: int,
     *     current: int,
     *     days_1_30: int,
     *     days_31_60: int,
     *     days_61_90: int,
     *     days_over_90: int,
     *     customer_count: int
     *   }
     * }
     */
    public function accountsReceivableAging(Company $company, ?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();

        // Get all unpaid/partially paid invoices
        $invoices = Invoice::where('company_id', $company->id)
            ->whereIn('status', ['sent', 'partial'])
            ->where('invoice_date', '<=', $asOfDate)
            ->where('balance_due', '>', 0)
            ->with(['customer'])
            ->get();

        $customerAging = $invoices->groupBy('customer_id')->map(function ($customerInvoices, $customerId) use ($asOfDate) {
            $customer = $customerInvoices->first()->customer;
            $aging = $this->calculateAgingBuckets($customerInvoices, $asOfDate, 'invoice_date', 'balance_due');

            return [
                'customer_id' => $customerId,
                'customer_name' => $customer->name ?? 'Unknown Customer',
                'customer_code' => $customer->customer_code ?? '',
                'total_outstanding' => $aging['total'],
                'current' => $aging['current'],
                'days_1_30' => $aging['days_1_30'],
                'days_31_60' => $aging['days_31_60'],
                'days_61_90' => $aging['days_61_90'],
                'days_over_90' => $aging['days_over_90'],
                'invoice_count' => $customerInvoices->count(),
                'oldest_invoice_date' => $customerInvoices->min('invoice_date'),
                'largest_invoice_amount' => $customerInvoices->max('balance_due'),
            ];
        })->sortByDesc('total_outstanding');

        // Calculate summary totals
        $summary = [
            'total_outstanding' => $customerAging->sum('total_outstanding'),
            'current' => $customerAging->sum('current'),
            'days_1_30' => $customerAging->sum('days_1_30'),
            'days_31_60' => $customerAging->sum('days_31_60'),
            'days_61_90' => $customerAging->sum('days_61_90'),
            'days_over_90' => $customerAging->sum('days_over_90'),
            'customer_count' => $customerAging->count(),
        ];

        return [
            'as_of_date' => $asOfDate->format('d M Y'),
            'company' => $company->name,
            'customers' => $customerAging->values()->toArray(),
            'summary' => $summary,
        ];
    }

    /**
     * Generate Accounts Payable aging report.
     *
     * @return array{
     *   as_of_date: string,
     *   company: string,
     *   suppliers: array,
     *   summary: array{
     *     total_outstanding: int,
     *     current: int,
     *     days_1_30: int,
     *     days_31_60: int,
     *     days_61_90: int,
     *     days_over_90: int,
     *     supplier_count: int
     *   }
     * }
     */
    public function accountsPayableAging(Company $company, ?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();

        // Get all unpaid/partially paid goods receipts (bills)
        $goodsReceipts = GoodsReceipt::where('company_id', $company->id)
            ->whereIn('status', ['received', 'partial'])
            ->where('receipt_date', '<=', $asOfDate)
            ->where('balance_due', '>', 0)
            ->with(['supplier'])
            ->get();

        $supplierAging = $goodsReceipts->groupBy('supplier_id')->map(function ($supplierReceipts, $supplierId) use ($asOfDate) {
            $supplier = $supplierReceipts->first()->supplier;
            $aging = $this->calculateAgingBuckets($supplierReceipts, $asOfDate, 'receipt_date', 'balance_due');

            return [
                'supplier_id' => $supplierId,
                'supplier_name' => $supplier->name ?? 'Unknown Supplier',
                'supplier_code' => $supplier->supplier_code ?? '',
                'total_outstanding' => $aging['total'],
                'current' => $aging['current'],
                'days_1_30' => $aging['days_1_30'],
                'days_31_60' => $aging['days_31_60'],
                'days_61_90' => $aging['days_61_90'],
                'days_over_90' => $aging['days_over_90'],
                'bill_count' => $supplierReceipts->count(),
                'oldest_bill_date' => $supplierReceipts->min('receipt_date'),
                'largest_bill_amount' => $supplierReceipts->max('balance_due'),
            ];
        })->sortByDesc('total_outstanding');

        // Calculate summary totals
        $summary = [
            'total_outstanding' => $supplierAging->sum('total_outstanding'),
            'current' => $supplierAging->sum('current'),
            'days_1_30' => $supplierAging->sum('days_1_30'),
            'days_31_60' => $supplierAging->sum('days_31_60'),
            'days_61_90' => $supplierAging->sum('days_61_90'),
            'days_over_90' => $supplierAging->sum('days_over_90'),
            'supplier_count' => $supplierAging->count(),
        ];

        return [
            'as_of_date' => $asOfDate->format('d M Y'),
            'company' => $company->name,
            'suppliers' => $supplierAging->values()->toArray(),
            'summary' => $summary,
        ];
    }

    /**
     * Get detailed aging for a specific customer.
     */
    public function customerAgingDetail(Company $company, int $customerId, ?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();

        $customer = Customer::where('company_id', $company->id)->findOrFail($customerId);

        $invoices = Invoice::where('company_id', $company->id)
            ->where('customer_id', $customerId)
            ->whereIn('status', ['sent', 'partial'])
            ->where('invoice_date', '<=', $asOfDate)
            ->where('balance_due', '>', 0)
            ->orderBy('invoice_date')
            ->get();

        $invoiceDetails = $invoices->map(function ($invoice) use ($asOfDate) {
            $invoiceDate = Carbon::parse($invoice->invoice_date);
            $daysOutstanding = (int) $invoiceDate->diffInDays($asOfDate);
            $agingBucket = $this->getAgingBucket($daysOutstanding);

            return [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date,
                'due_date' => $invoice->due_date,
                'total_amount' => $invoice->total_amount,
                'amount_paid' => $invoice->amount_paid,
                'balance_due' => $invoice->balance_due,
                'days_outstanding' => $daysOutstanding,
                'aging_bucket' => $agingBucket,
                'is_overdue' => $invoice->due_date && Carbon::parse($invoice->due_date)->lt($asOfDate),
            ];
        });

        $aging = $this->calculateAgingBuckets($invoices, $asOfDate, 'invoice_date', 'balance_due');

        return [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'customer_code' => $customer->customer_code,
                'credit_limit' => $customer->credit_limit,
                'current_balance' => $customer->currentBalance(),
            ],
            'as_of_date' => $asOfDate->format('d M Y'),
            'aging_summary' => $aging,
            'invoices' => $invoiceDetails->toArray(),
        ];
    }

    /**
     * Calculate aging buckets for a collection of documents.
     */
    private function calculateAgingBuckets(Collection $documents, Carbon $asOfDate, string $dateField, string $amountField): array
    {
        $buckets = [
            'total' => 0,
            'current' => 0,
            'days_1_30' => 0,
            'days_31_60' => 0,
            'days_61_90' => 0,
            'days_over_90' => 0,
        ];

        foreach ($documents as $document) {
            $amount = $document->{$amountField};
            $documentDate = Carbon::parse($document->{$dateField});
            $daysOutstanding = (int) $documentDate->diffInDays($asOfDate);
            
            $buckets['total'] += $amount;

            if ($daysOutstanding <= 0) {
                $buckets['current'] += $amount;
            } elseif ($daysOutstanding <= 30) {
                $buckets['days_1_30'] += $amount;
            } elseif ($daysOutstanding <= 60) {
                $buckets['days_31_60'] += $amount;
            } elseif ($daysOutstanding <= 90) {
                $buckets['days_61_90'] += $amount;
            } else {
                $buckets['days_over_90'] += $amount;
            }
        }

        return $buckets;
    }

    /**
     * Get the aging bucket name for a number of days.
     */
    private function getAgingBucket(int $days): string
    {
        if ($days <= 0) {
            return 'Current';
        } elseif ($days <= 30) {
            return '1-30 Days';
        } elseif ($days <= 60) {
            return '31-60 Days';
        } elseif ($days <= 90) {
            return '61-90 Days';
        } else {
            return 'Over 90 Days';
        }
    }

    /**
     * Generate aging summary with percentages.
     */
    public function agingSummaryWithPercentages(array $aging): array
    {
        $total = $aging['total'];
        
        if ($total == 0) {
            return array_merge($aging, [
                'current_pct' => 0,
                'days_1_30_pct' => 0,
                'days_31_60_pct' => 0,
                'days_61_90_pct' => 0,
                'days_over_90_pct' => 0,
            ]);
        }

        return array_merge($aging, [
            'current_pct' => round(($aging['current'] / $total) * 100, 1),
            'days_1_30_pct' => round(($aging['days_1_30'] / $total) * 100, 1),
            'days_31_60_pct' => round(($aging['days_31_60'] / $total) * 100, 1),
            'days_61_90_pct' => round(($aging['days_61_90'] / $total) * 100, 1),
            'days_over_90_pct' => round(($aging['days_over_90'] / $total) * 100, 1),
        ]);
    }
}