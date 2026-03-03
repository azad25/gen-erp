<?php

namespace App\Services;

use App\Models\Company;
use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Services\ContactService as DomainContactService;
use App\Domain\Customer\DTOs\CreateCustomerData;

/**
 * Legacy ContactService - delegates to domain service for backward compatibility.
 * @deprecated Use App\Domain\Customer\Services\ContactService directly
 */
class ContactService
{
    public function __construct(
        private readonly DomainContactService $domainContactService,
    ) {}

    /**
     * Create customer with DTO.
     */
    public function createCustomer(CreateCustomerData $data): Customer
    {
        return $this->domainContactService->createCustomer($data);
    }

    /**
     * Legacy method - Create customer with raw arrays (for backward compatibility).
     */
    public function createCustomerLegacy(Company $company, array $data, array $customFields = []): Customer
    {
        return $this->domainContactService->createCustomerLegacy($company, $data, $customFields);
    }

    /**
     * Update customer with DTO.
     */
    public function updateCustomer(Customer $customer, CreateCustomerData $data): Customer
    {
        return $this->domainContactService->updateCustomer($customer, $data);
    }

    /**
     * Legacy method - Update customer with raw arrays (for backward compatibility).
     */
    public function updateCustomerLegacy(Customer $customer, array $data, array $customFields = []): Customer
    {
        return $this->domainContactService->updateCustomerLegacy($customer, $data, $customFields);
    }

    /**
     * @throws \RuntimeException
     */
    public function deleteCustomer(Customer $customer): void
    {
        $this->domainContactService->deleteCustomer($customer);
    }

    /**
     * @return array{transactions: \Illuminate\Database\Eloquent\Collection, opening_balance: int, closing_balance: int}
     */
    public function getCustomerStatement(Customer $customer, \Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        return $this->domainContactService->getCustomerStatement($customer, $from, $to);
    }

    public function recordCustomerTransaction(
        Customer $customer,
        string $type,
        int $amount,
        string $description,
        ?\Illuminate\Database\Eloquent\Model $reference = null,
    ): \App\Models\CustomerTransaction {
        return $this->domainContactService->recordCustomerTransaction($customer, $type, $amount, $description, $reference);
    }

    /**
     * Paginated customer listing with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateCustomers(Company $company, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->domainContactService->paginateCustomers($company, $filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $customFields
     */
    public function createSupplier(Company $company, array $data, array $customFields = []): \App\Models\Supplier
    {
        return $this->domainContactService->createSupplier($company, $data, $customFields);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $customFields
     */
    public function updateSupplier(\App\Models\Supplier $supplier, array $data, array $customFields = []): \App\Models\Supplier
    {
        return $this->domainContactService->updateSupplier($supplier, $data, $customFields);
    }

    /**
     * @throws \RuntimeException
     */
    public function deleteSupplier(\App\Models\Supplier $supplier): void
    {
        $this->domainContactService->deleteSupplier($supplier);
    }

    /**
     * @return array{transactions: \Illuminate\Database\Eloquent\Collection, opening_balance: int, closing_balance: int}
     */
    public function getSupplierStatement(\App\Models\Supplier $supplier, \Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        return $this->domainContactService->getSupplierStatement($supplier, $from, $to);
    }

    public function recordSupplierTransaction(
        \App\Models\Supplier $supplier,
        string $type,
        int $amount,
        string $description,
        ?\Illuminate\Database\Eloquent\Model $reference = null,
    ): \App\Models\SupplierTransaction {
        return $this->domainContactService->recordSupplierTransaction($supplier, $type, $amount, $description, $reference);
    }

    /**
     * @return array{net: int, tds_amount: int, vds_amount: int}
     */
    public function calculateTdsVds(\App\Models\Supplier $supplier, int $grossAmount): array
    {
        return $this->domainContactService->calculateTdsVds($supplier, $grossAmount);
    }

    /**
     * Paginated supplier listing with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateSuppliers(Company $company, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->domainContactService->paginateSuppliers($company, $filters, $perPage);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(string $type, string $query, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return $this->domainContactService->search($type, $query, $limit);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{created: int, failed: int, errors: array<int, array{row: int, error: string}>}
     */
    public function importContacts(Company $company, string $type, array $rows): array
    {
        return $this->domainContactService->importContacts($company, $type, $rows);
    }

    /**
     * Find active customer or fail.
     */
    public function findActiveOrFail(int $customerId): Customer
    {
        return $this->domainContactService->findActiveOrFail($customerId);
    }

    /**
     * Record customer transaction.
     */
    public function recordTransaction(int $customerId, string $type, int $amount, string $description, ?\Illuminate\Database\Eloquent\Model $reference = null): \App\Models\CustomerTransaction
    {
        return $this->domainContactService->recordTransaction($customerId, $type, $amount, $description, $reference);
    }
}