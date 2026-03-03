<?php

namespace App\Domain\Contact\Services;

use App\Domain\Auth\Models\Company;
use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Models\CustomerTransaction;
use App\Domain\Purchase\Models\Supplier;
use App\Domain\System\Services\SequenceService;
use Carbon\Carbon;

/**
 * Manages customer and supplier contact operations.
 */
class ContactService
{
    public function __construct(
        private SequenceService $sequenceService
    ) {}

    /**
     * Create a new customer.
     */
    public function createCustomer(Company $company, array $data, array $customFields = []): Customer
    {
        $data['company_id'] = $company->id;
        $data['customer_code'] = $this->sequenceService->next('customer', $company->id);

        $customer = Customer::create($data);

        if (!empty($customFields)) {
            // Handle custom fields via CustomFieldService
            $cfService = app(\App\Domain\Shared\Services\CustomFieldService::class);
            $cfService->saveValues('customer', $customer->id, $customFields);
        }

        return $customer;
    }

    /**
     * Create a new supplier.
     */
    public function createSupplier(Company $company, array $data, array $customFields = []): Supplier
    {
        $data['company_id'] = $company->id;
        $data['supplier_code'] = $this->sequenceService->next('supplier', $company->id);

        $supplier = Supplier::create($data);

        if (!empty($customFields)) {
            // Handle custom fields via CustomFieldService
            $cfService = app(\App\Domain\Shared\Services\CustomFieldService::class);
            $cfService->saveValues('supplier', $supplier->id, $customFields);
        }

        return $supplier;
    }

    /**
     * Record a customer transaction.
     */
    public function recordCustomerTransaction(Customer $customer, string $type, int $amount, string $description): CustomerTransaction
    {
        $currentBalance = $customer->currentBalance();
        $newBalance = $currentBalance + $amount;

        return CustomerTransaction::create([
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $description,
            'transaction_date' => now()->toDateString(),
        ]);
    }

    /**
     * Record a supplier transaction.
     */
    public function recordSupplierTransaction(Supplier $supplier, string $type, int $amount, string $description, $reference = null): void
    {
        $currentBalance = $supplier->currentBalance();
        $newBalance = $currentBalance + $amount;
        
        $supplier->transactions()->create([
            'company_id' => $supplier->company_id,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $description,
            'transaction_date' => now()->toDateString(),
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);
    }

    /**
     * Calculate TDS/VDS deductions for a supplier.
     */
    public function calculateTdsVds(Supplier $supplier, int $grossAmount): array
    {
        $tdsAmount = (int) round($grossAmount * ($supplier->tds_rate / 100));
        $vdsAmount = (int) round($grossAmount * ($supplier->vds_rate / 100));
        $netAmount = $grossAmount - $tdsAmount - $vdsAmount;

        return [
            'tds_amount' => $tdsAmount,
            'vds_amount' => $vdsAmount,
            'net' => $netAmount,
        ];
    }

    /**
     * Import contacts from array data.
     */
    public function importContacts(Company $company, string $type, array $contacts): array
    {
        $created = 0;
        $failed = 0;
        $errors = [];

        foreach ($contacts as $contactData) {
            try {
                if (empty($contactData['name'])) {
                    throw new \InvalidArgumentException('Name is required');
                }

                if ($type === 'customer') {
                    $this->createCustomer($company, $contactData);
                } else {
                    $this->createSupplier($company, $contactData);
                }

                $created++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = $e->getMessage();
            }
        }

        return compact('created', 'failed', 'errors');
    }

    /**
     * Get customer statement for a date range.
     */
    public function getCustomerStatement(Customer $customer, Carbon $fromDate, Carbon $toDate): array
    {
        // Get transactions in the date range
        $transactions = CustomerTransaction::where('customer_id', $customer->id)
            ->whereBetween('transaction_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->orderBy('transaction_date')
            ->get();

        // Calculate opening balance (transactions before the date range)
        $openingBalance = CustomerTransaction::where('customer_id', $customer->id)
            ->where('transaction_date', '<', $fromDate->toDateString())
            ->orderBy('transaction_date', 'desc')
            ->first()?->balance_after ?? $customer->opening_balance ?? 0;

        // Calculate closing balance
        $closingBalance = $transactions->last()?->balance_after ?? $openingBalance;

        return [
            'transactions' => $transactions,
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
        ];
    }
}