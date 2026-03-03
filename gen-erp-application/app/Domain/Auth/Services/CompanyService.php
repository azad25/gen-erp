<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Actions\UpdateCompanyAction;
use App\Domain\Auth\Contracts\CompanyServiceInterface;
use App\Domain\Auth\DTOs\UpdateCompanyData;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Core company management service.
 */
class CompanyService implements CompanyServiceInterface
{
    public function __construct(
        private readonly UpdateCompanyAction $updateCompanyAction,
    ) {}

    /**
     * Get paginated companies with search functionality.
     */
    public function paginateCompanies(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Company::query();

        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where('name', 'LIKE', "%{$search}%");
        }

        return $query->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Update an existing company.
     */
    public function updateCompany(Company $company, UpdateCompanyData $data): Company
    {
        return $this->updateCompanyAction->execute($company, $data);
    }

    /**
     * Get company with relationships.
     */
    public function getCompanyWithRelations(Company $company): Company
    {
        return $company->load(['users', 'branches', 'warehouses']);
    }

    // ═══════════════════════════════════════════════
    // Branch Management
    // ═══════════════════════════════════════════════

    /**
     * Get paginated branches for a company.
     */
    public function getBranches(int $companyId, ?string $search = null, ?bool $isActive = null, int $perPage = 15): LengthAwarePaginator
    {
        return Branch::query()
            ->where('company_id', $companyId)
            ->when($search, fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->when($isActive !== null, fn ($q) => $q->where('is_active', $isActive))
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get a specific branch.
     */
    public function getBranch(int $companyId, int $id): Branch
    {
        return Branch::where('company_id', $companyId)->findOrFail($id);
    }

    /**
     * Create a branch.
     */
    public function createBranch(int $companyId, array $data): Branch
    {
        $data['company_id'] = $companyId;
        return Branch::create($data);
    }

    /**
     * Update a branch.
     */
    public function updateBranch(Branch $branch, array $data): Branch
    {
        $branch->update($data);
        return $branch->fresh();
    }

    /**
     * Delete a branch.
     */
    public function deleteBranch(Branch $branch): void
    {
        $branch->delete();
    }
}