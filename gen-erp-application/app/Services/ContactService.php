<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Manages customer and supplier operations including transactions and statements.
 */
class ContactService
{
    public function __construct(
        private readonly CustomFieldService $customFieldService,
    ) {}

    // ═══════════════════════════════════════════
    // Customer Operations
    // ═══════════════════════════════════════════

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $customFields
     */
    public function createCustomer(Company $company, array $data, array $customFields = []): Customer
    {
        return DB::transaction(function () use ($company, $data, $customFields): Customer {
            $data['company_id'] = $company->id;
            $customer = Customer::withoutGlobalScopes()->create($data);

            if ($customFields !== []) {
                $this->customFieldService->saveValues('customer', $customer->id, $customFields);
            }

            return $customer;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $customFields
     */
    public function updateCustomer(Customer $customer, array $data, array $customFields = []): Customer
    {
        return DB::transaction(function () use ($customer, $data, $customFields): Customer {
            $customer->update($data);

            if ($customFields !== []) {
                $this->customFieldService->saveValues('customer', $customer->id, $customFields);
            }

            return $customer->fresh();
        });
    }

    /**
     * @throws RuntimeException
     */
    public function deleteCustomer(Customer $customer): void
    {
        // TODO: Phase 4 — check for open invoices
        if ($this->hasOpenInvoices($customer)) {
            throw new RuntimeException(
                __('Cannot delete a customer with open invoices.')
            );
        }

        $customer->delete();
    }

    /**
     * @return array{transactions: Collection<int, CustomerTransaction>, opening_balance: int, closing_balance: int}
     */
    public function getCustomerStatement(Customer $customer, Carbon $from, Carbon $to): array
    {
        $transactions = $customer->transactions()
            ->forDateRange($from, $to)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        // Opening balance = customer opening + all txns before $from
        $priorSum = $customer->transactions()
            ->where('transaction_date', '<', $from)
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->value('total');

        $openingBalance = $customer->opening_balance + (int) $priorSum;
        $periodSum = $transactions->sum('amount');
        $closingBalance = $openingBalance + $periodSum;

        return [
            'transactions' => $transactions,
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
        ];
    }

    public function recordCustomerTransaction(
        Customer $customer,
        string $type,
        int $amount,
        string $description,
        ?Model $reference = null,
    ): CustomerTransaction {
        $balanceAfter = $customer->currentBalance() + $amount;

        return CustomerTransaction::withoutGlobalScopes()->create([
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'type' => $type,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->getKey(),
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'description' => $description,
            'transaction_date' => now()->toDateString(),
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);
    }

    // ═══════════════════════════════════════════
    // Supplier Operations
    // ═══════════════════════════════════════════

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $customFields
     */
    public function createSupplier(Company $company, array $data, array $customFields = []): Supplier
    {
        return DB::transaction(function () use ($company, $data, $customFields): Supplier {
            $data['company_id'] = $company->id;
            $supplier = Supplier::withoutGlobalScopes()->create($data);

            if ($customFields !== []) {
                $this->customFieldService->saveValues('supplier', $supplier->id, $customFields);
            }

            return $supplier;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $customFields
     */
    public function updateSupplier(Supplier $supplier, array $data, array $customFields = []): Supplier
    {
        return DB::transaction(function () use ($supplier, $data, $customFields): Supplier {
            $supplier->update($data);

            if ($customFields !== []) {
                $this->customFieldService->saveValues('supplier', $supplier->id, $customFields);
            }

            return $supplier->fresh();
        });
    }

    /**
     * @throws RuntimeException
     */
    public function deleteSupplier(Supplier $supplier): void
    {
        // TODO: Phase 4 — check for open purchase orders
        $supplier->delete();
    }

    /**
     * @return array{transactions: Collection<int, SupplierTransaction>, opening_balance: int, closing_balance: int}
     */
    public function getSupplierStatement(Supplier $supplier, Carbon $from, Carbon $to): array
    {
        $transactions = $supplier->transactions()
            ->forDateRange($from, $to)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $priorSum = $supplier->transactions()
            ->where('transaction_date', '<', $from)
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->value('total');

        $openingBalance = $supplier->opening_balance + (int) $priorSum;
        $closingBalance = $openingBalance + $transactions->sum('amount');

        return [
            'transactions' => $transactions,
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
        ];
    }

    public function recordSupplierTransaction(
        Supplier $supplier,
        string $type,
        int $amount,
        string $description,
        ?Model $reference = null,
    ): SupplierTransaction {
        $balanceAfter = $supplier->currentBalance() + $amount;

        return SupplierTransaction::withoutGlobalScopes()->create([
            'company_id' => $supplier->company_id,
            'supplier_id' => $supplier->id,
            'type' => $type,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->getKey(),
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'description' => $description,
            'transaction_date' => now()->toDateString(),
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);
    }

    /**
     * @return array{net: int, tds_amount: int, vds_amount: int}
     */
    public function calculateTdsVds(Supplier $supplier, int $grossAmount): array
    {
        return $supplier->netPaymentAmount($grossAmount);
    }

    // ═══════════════════════════════════════════
    // Shared
    // ═══════════════════════════════════════════

    /**
     * @return Collection<int, Customer>|Collection<int, Supplier>
     */
    public function search(string $type, string $query, int $limit = 20): Collection
    {
        $term = mb_strtolower(trim($query));
        $model = $type === 'customer' ? Customer::class : Supplier::class;

        return $model::active()
            ->where(function ($q) use ($term): void {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{created: int, failed: int, errors: array<int, array{row: int, error: string}>}
     */
    public function importContacts(Company $company, string $type, array $rows): array
    {
        $created = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                if ($type === 'customer') {
                    $this->createCustomer($company, $row);
                } else {
                    $this->createSupplier($company, $row);
                }
                $created++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = ['row' => $index + 1, 'error' => $e->getMessage()];
            }
        }

        return compact('created', 'failed', 'errors');
    }

    private function hasOpenInvoices(Customer $customer): bool
    {
        // TODO: Phase 4 — implement real check against invoices
        return false;
    }
}
