<?php

namespace App\Domain\Customer\Contracts;

use App\Domain\Auth\Models\Company;
use App\Domain\Customer\DTOs\CreateCustomerData;
use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Models\CustomerTransaction;
use App\Domain\Customer\Models\ContactGroup;
use App\Domain\Purchase\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface for contact service operations.
 */
interface ContactServiceInterface
{
    /**
     * Create a new customer.
     */
    public function createCustomer(CreateCustomerData $data): Customer;

    /**
     * Create a new customer (legacy method).
     */
    public function createCustomerLegacy(Company $company, array $data, array $customFields = []): Customer;

    /**
     * Update a customer.
     */
    public function updateCustomer(Customer $customer, CreateCustomerData $data): Customer;

    /**
     * Update a customer (legacy method).
     */
    public function updateCustomerLegacy(Customer $customer, array $data, array $customFields = []): Customer;

    /**
     * Delete a customer.
     */
    public function deleteCustomer(Customer $customer): void;

    /**
     * Get customer statement.
     */
    public function getCustomerStatement(Customer $customer, Carbon $from, Carbon $to): array;

    /**
     * Record customer transaction.
     */
    public function recordCustomerTransaction(
        Customer $customer,
        string $type,
        int $amount,
        string $description,
        ?Model $reference = null
    ): CustomerTransaction;

    /**
     * Paginate customers.
     */
    public function paginateCustomers(Company $company, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Create a new supplier.
     */
    public function createSupplier(Company $company, array $data, array $customFields = []): Supplier;

    /**
     * Update a supplier.
     */
    public function updateSupplier(Supplier $supplier, array $data, array $customFields = []): Supplier;

    /**
     * Delete a supplier.
     */
    public function deleteSupplier(Supplier $supplier): void;

    /**
     * Get supplier statement.
     */
    public function getSupplierStatement(Supplier $supplier, Carbon $from, Carbon $to): array;

    /**
     * Record supplier transaction.
     */
    public function recordSupplierTransaction(
        Supplier $supplier,
        string $type,
        int $amount,
        string $description,
        ?Model $reference = null
    ): \App\Domain\Purchase\Models\SupplierTransaction;

    /**
     * Calculate TDS/VDS for supplier.
     */
    public function calculateTdsVds(Supplier $supplier, int $grossAmount): array;

    /**
     * Paginate suppliers.
     */
    public function paginateSuppliers(Company $company, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Search contacts.
     */
    public function search(string $type, string $query, int $limit = 20): Collection;

    /**
     * Import contacts.
     */
    public function importContacts(Company $company, string $type, array $rows): array;

    /**
     * Find active customer or fail.
     */
    public function findActiveOrFail(int $customerId): Customer;

    /**
     * Record transaction.
     */
    public function recordTransaction(int $customerId, string $type, int $amount, string $description, ?Model $reference = null): CustomerTransaction;

    /**
     * Get paginated contact groups for a company.
     */
    public function getContactGroups(int $companyId, ?string $search = null, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Get a specific contact group.
     */
    public function getContactGroup(int $companyId, int $id): ContactGroup;

    /**
     * Create a contact group.
     */
    public function createContactGroup(int $companyId, array $data): ContactGroup;

    /**
     * Update a contact group.
     */
    public function updateContactGroup(ContactGroup $contactGroup, array $data): ContactGroup;

    /**
     * Delete a contact group.
     */
    public function deleteContactGroup(ContactGroup $contactGroup): void;
}