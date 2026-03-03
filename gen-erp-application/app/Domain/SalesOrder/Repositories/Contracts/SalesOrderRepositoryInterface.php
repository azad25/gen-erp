<?php

namespace App\Domain\SalesOrder\Repositories\Contracts;

use App\Domain\SalesOrder\Models\SalesOrder;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SalesOrderRepositoryInterface
{
    /**
     * Find sales order by ID for the given company.
     */
    public function findByIdForCompany(int $id, Company $company): ?SalesOrder;

    /**
     * Get paginated sales orders for a company with filters.
     */
    public function paginateForCompany(Company $company, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new sales order.
     */
    public function create(array $data): SalesOrder;

    /**
     * Update an existing sales order.
     */
    public function update(SalesOrder $salesOrder, array $data): SalesOrder;

    /**
     * Delete a sales order.
     */
    public function delete(SalesOrder $salesOrder): bool;

    /**
     * Find orders by status for a company.
     */
    public function findByStatusForCompany(string $status, Company $company): Collection;

    /**
     * Get recent orders for dashboard.
     */
    public function getRecentForCompany(Company $company, int $limit = 10): Collection;

    /**
     * Find orders ready for invoicing.
     */
    public function findReadyForInvoicing(Company $company): Collection;
}