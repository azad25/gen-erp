<?php

namespace App\Domain\Customer\Repositories\Contracts;

use App\Domain\Customer\Models\Customer;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CustomerRepositoryInterface
{
    /**
     * Find customer by ID for the given company.
     */
    public function findByIdForCompany(int $id, Company $company): ?Customer;

    /**
     * Get paginated customers for a company with filters.
     */
    public function paginateForCompany(Company $company, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new customer.
     */
    public function create(array $data): Customer;

    /**
     * Update an existing customer.
     */
    public function update(Customer $customer, array $data): Customer;

    /**
     * Delete a customer.
     */
    public function delete(Customer $customer): bool;

    /**
     * Find active customers for a company.
     */
    public function findActiveForCompany(Company $company): Collection;

    /**
     * Search customers by name, phone, or email.
     */
    public function searchByName(string $query, Company $company, int $limit = 20): Collection;

    /**
     * Find customer by ID or fail.
     */
    public function findActiveOrFail(int $customerId): Customer;

    /**
     * Get customers with outstanding balances.
     */
    public function findWithOutstandingBalances(Company $company): Collection;
}