<?php

namespace App\Domain\Customer\Services;

use App\Domain\Auth\Models\Company;
use App\Domain\Customer\Contracts\ContactServiceInterface;
use App\Domain\Customer\DTOs\CreateCustomerData;
use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Models\ContactGroup;
use App\Domain\Customer\Models\CustomerTransaction;
use App\Domain\Purchase\Models\Supplier;
use App\Domain\Purchase\Models\SupplierTransaction;
use App\Domain\Customer\Repositories\Contracts\CustomerRepositoryInterface;
use App\Domain\Customer\Actions\RecordCustomerTransactionAction;
use App\Domain\Shared\Services\CustomFieldService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Manages customer and supplier operations including transactions and statements.
 */
class ContactService implements ContactServiceInterface
{
    public function __construct(
        private readonly CustomFieldService $customFieldService,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly RecordCustomerTransactionAction $recordCustomerTransactionAction,
    ) {}

    // ═══════════════════════════════════════════
    // Customer Operations
    // ═══════════════════════════════════════════

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $customFields
     */
    /**
     * Create customer with DTO.
     */
    public function createCustomer(CreateCustomerData $data): Customer
    {
        return DB::transaction(function () use ($data): Customer {
            $customer = $this->customerRepository->create($data->toArray());

            if ($data->customFields) {
                $this->customFieldService->saveValues('customer', $customer->id, $data->customFields);
            }

            return $customer;
        });
    }

    /**
     * Legacy method - Create customer with raw arrays (for backward compatibility).
     */
    public function createCustomerLegacy(Company $company, array $data, array $customFields = []): Customer
    {
        return DB::transaction(function () use ($company, $data, $customFields): Customer {
            $data['company_id'] = $company->id;
            $customer = $this->customerRepository->create($data);

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
    /**
     * Update customer with DTO.
     */
    public function updateCustomer(Customer $customer, CreateCustomerData $data): Customer
    {
        return DB::transaction(function () use ($customer, $data): Customer {
            $customer = $this->customerRepository->update($customer, $data->toArray());

            if ($data->customFields) {
                $this->customFieldService->saveValues('customer', $customer->id, $data->customFields);
            }

            return $customer;
        });
    }

    /**
     * Legacy method - Update customer with raw arrays (for backward compatibility).
     */
    public function updateCustomerLegacy(Customer $customer, array $data, array $customFields = []): Customer
    {
        return DB::transaction(function () use ($customer, $data, $customFields): Customer {
            $customer = $this->customerRepository->update($customer, $data);

            if ($customFields !== []) {
                $this->customFieldService->saveValues('customer', $customer->id, $customFields);
            }

            return $customer;
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

        $this->customerRepository->delete($customer);
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
        return $this->recordCustomerTransactionAction->execute($customer, $type, $amount, $description, $reference);
    }

    /**
     * Paginated customer listing with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateCustomers(Company $company, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->customerRepository->paginateForCompany($company, $filters, $perPage);
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
        if ($this->hasOpenPurchaseOrders($supplier)) {
            throw new RuntimeException(
                __('Cannot delete a supplier with open purchase orders.')
            );
        }
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

    /**
     * Paginated supplier listing with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateSuppliers(Company $company, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Supplier::query()
            ->where('company_id', $company->id)
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(function ($q) use ($s): void {
                $q->where('name', 'LIKE', "%{$s}%")
                    ->orWhere('phone', 'LIKE', "%{$s}%")
                    ->orWhere('email', 'LIKE', "%{$s}%");
            }))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['contact_group_id'] ?? null, fn ($q, $id) => $q->where('contact_group_id', $id))
            ->with(['contactGroup'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    // ═══════════════════════════════════════════
    // Shared
    // ═══════════════════════════════════════════

    /**
     * @return Collection<int, Customer>|Collection<int, Supplier>
     */
    public function search(string $type, string $query, int $limit = 20): Collection
    {
        if ($type === 'customer') {
            return $this->customerRepository->searchByName($query, activeCompany(), $limit);
        }

        // Fallback to existing supplier search logic
        $term = mb_strtolower(trim($query));
        $model = Supplier::class;

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
                    $this->createCustomerLegacy($company, $row);
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

    /**
     * Find active customer or fail.
     */
    public function findActiveOrFail(int $customerId): Customer
    {
        return $this->customerRepository->findActiveOrFail($customerId);
    }

    /**
     * Record customer transaction.
     */
    public function recordTransaction(int $customerId, string $type, int $amount, string $description, ?Model $reference = null): CustomerTransaction
    {
        $customer = Customer::findOrFail($customerId);
        return $this->recordCustomerTransaction($customer, $type, $amount, $description, $reference);
    }

    private function hasOpenInvoices(Customer $customer): bool
    {
        return $customer->invoices()
            ->where('status', '!=', 'paid')
            ->exists();
    }

    private function hasOpenPurchaseOrders(Supplier $supplier): bool
    {
        return $supplier->purchaseOrders()
            ->where('status', '!=', 'received')
            ->exists();
    }

    // ═══════════════════════════════════════════
    // Contact Group Management
    // ═══════════════════════════════════════════

    /**
     * Get paginated contact groups for a company.
     */
    public function getContactGroups(int $companyId, ?string $search = null, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return ContactGroup::query()
            ->where('company_id', $companyId)
            ->when($search, fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get a specific contact group.
     */
    public function getContactGroup(int $companyId, int $id): ContactGroup
    {
        return ContactGroup::where('company_id', $companyId)->findOrFail($id);
    }

    /**
     * Create a contact group.
     */
    public function createContactGroup(int $companyId, array $data): ContactGroup
    {
        $data['company_id'] = $companyId;
        return ContactGroup::create($data);
    }

    /**
     * Update a contact group.
     */
    public function updateContactGroup(ContactGroup $contactGroup, array $data): ContactGroup
    {
        $contactGroup->update($data);
        return $contactGroup->fresh();
    }

    /**
     * Delete a contact group.
     */
    public function deleteContactGroup(ContactGroup $contactGroup): void
    {
        $contactGroup->delete();
    }
}
