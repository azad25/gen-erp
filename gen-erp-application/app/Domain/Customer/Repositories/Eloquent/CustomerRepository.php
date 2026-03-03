<?php

namespace App\Domain\Customer\Repositories\Eloquent;

use App\Domain\Customer\Models\Customer;
use App\Models\Company;
use App\Domain\Customer\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository implements CustomerRepositoryInterface
{
    /**
     * Find customer by ID for the given company.
     */
    public function findByIdForCompany(int $id, Company $company): ?Customer
    {
        return Customer::where('company_id', $company->id)
            ->with(['contactGroup'])
            ->find($id);
    }

    /**
     * Get paginated customers for a company with filters.
     */
    public function paginateForCompany(Company $company, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Customer::query()
            ->where('company_id', $company->id)
            ->when($filters['search'] ?? null, fn($q, $s) => $this->applySearchFilter($q, $s))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['contact_group_id'] ?? null, fn($q, $id) => $q->where('contact_group_id', $id))
            ->with(['contactGroup']) // Always eager load relationships
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Create a new customer.
     */
    public function create(array $data): Customer
    {
        return Customer::withoutGlobalScopes()->create($data);
    }

    /**
     * Update an existing customer.
     */
    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer->fresh(['contactGroup']);
    }

    /**
     * Delete a customer.
     */
    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }

    /**
     * Find active customers for a company.
     */
    public function findActiveForCompany(Company $company): Collection
    {
        return Customer::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Search customers by name, phone, or email.
     */
    public function searchByName(string $query, Company $company, int $limit = 20): Collection
    {
        $term = mb_strtolower(trim($query));
        
        return Customer::where('company_id', $company->id)
            ->where('is_active', true)
            ->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                  ->orWhere('phone', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            })
            ->limit($limit)
            ->get(['id', 'name', 'phone', 'email']);
    }

    /**
     * Find customer by ID or fail.
     */
    public function findActiveOrFail(int $customerId): Customer
    {
        return Customer::where('is_active', true)
            ->findOrFail($customerId);
    }

    /**
     * Get customers with outstanding balances.
     */
    public function findWithOutstandingBalances(Company $company): Collection
    {
        return Customer::where('company_id', $company->id)
            ->where('is_active', true)
            ->whereHas('transactions', function ($query) {
                $query->selectRaw('SUM(amount) as balance')
                      ->havingRaw('SUM(amount) > 0');
            })
            ->with(['contactGroup'])
            ->get();
    }

    /**
     * Apply search filter to query.
     */
    private function applySearchFilter(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('phone', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%");
        });
    }
}