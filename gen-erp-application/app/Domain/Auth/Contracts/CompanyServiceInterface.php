<?php

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\DTOs\UpdateCompanyData;
use App\Domain\Auth\Models\Branch;
use App\Domain\Auth\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for company service operations.
 */
interface CompanyServiceInterface
{
    /**
     * Get paginated companies with search functionality.
     */
    public function paginateCompanies(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Update an existing company.
     */
    public function updateCompany(Company $company, UpdateCompanyData $data): Company;

    /**
     * Get company with relationships.
     */
    public function getCompanyWithRelations(Company $company): Company;

    /**
     * Get paginated branches for a company.
     */
    public function getBranches(int $companyId, ?string $search = null, ?bool $isActive = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a specific branch.
     */
    public function getBranch(int $companyId, int $id): Branch;

    /**
     * Create a branch.
     */
    public function createBranch(int $companyId, array $data): Branch;

    /**
     * Update a branch.
     */
    public function updateBranch(Branch $branch, array $data): Branch;

    /**
     * Delete a branch.
     */
    public function deleteBranch(Branch $branch): void;
}