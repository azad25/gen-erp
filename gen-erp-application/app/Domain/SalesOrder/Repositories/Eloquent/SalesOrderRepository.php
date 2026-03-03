<?php

namespace App\Domain\SalesOrder\Repositories\Eloquent;

use App\Domain\SalesOrder\Models\SalesOrder;
use App\Models\Company;
use App\Domain\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use App\Support\Enums\SalesOrderStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SalesOrderRepository implements SalesOrderRepositoryInterface
{
    /**
     * Find sales order by ID for the given company.
     */
    public function findByIdForCompany(int $id, Company $company): ?SalesOrder
    {
        return SalesOrder::where('company_id', $company->id)
            ->with(['customer', 'items.product'])
            ->find($id);
    }

    /**
     * Get paginated sales orders for a company with filters.
     */
    public function paginateForCompany(Company $company, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return SalesOrder::query()
            ->where('company_id', $company->id)
            ->when($filters['search'] ?? null, fn($q, $s) => $this->applySearchFilter($q, $s))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['customer_id'] ?? null, fn($q, $id) => $q->where('customer_id', $id))
            ->when($filters['from_date'] ?? null, fn($q, $date) => $q->where('order_date', '>=', $date))
            ->when($filters['to_date'] ?? null, fn($q, $date) => $q->where('order_date', '<=', $date))
            ->with(['customer', 'items.product']) // Always eager load relationships
            ->orderBy('order_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a new sales order.
     */
    public function create(array $data): SalesOrder
    {
        return SalesOrder::withoutGlobalScopes()->create($data);
    }

    /**
     * Update an existing sales order.
     */
    public function update(SalesOrder $salesOrder, array $data): SalesOrder
    {
        $salesOrder->update($data);
        return $salesOrder->fresh(['customer', 'items.product']);
    }

    /**
     * Delete a sales order.
     */
    public function delete(SalesOrder $salesOrder): bool
    {
        // Delete items first
        $salesOrder->items()->delete();
        return $salesOrder->delete();
    }

    /**
     * Find orders by status for a company.
     */
    public function findByStatusForCompany(string $status, Company $company): Collection
    {
        return SalesOrder::where('company_id', $company->id)
            ->where('status', $status)
            ->with(['customer', 'items.product'])
            ->orderBy('order_date', 'desc')
            ->get();
    }

    /**
     * Get recent orders for dashboard.
     */
    public function getRecentForCompany(Company $company, int $limit = 10): Collection
    {
        return SalesOrder::where('company_id', $company->id)
            ->with(['customer:id,name'])
            ->latest('order_date')
            ->limit($limit)
            ->get(['id', 'order_number', 'customer_id', 'total_amount', 'status', 'order_date']);
    }

    /**
     * Find orders ready for invoicing.
     */
    public function findReadyForInvoicing(Company $company): Collection
    {
        return SalesOrder::where('company_id', $company->id)
            ->where('status', SalesOrderStatus::CONFIRMED)
            ->whereDoesntHave('invoices') // Orders without invoices
            ->with(['customer', 'items.product'])
            ->orderBy('order_date')
            ->get();
    }

    /**
     * Apply search filter to query.
     */
    private function applySearchFilter(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('order_number', 'LIKE', "%{$search}%")
              ->orWhere('reference', 'LIKE', "%{$search}%")
              ->orWhereHas('customer', function ($customerQuery) use ($search) {
                  $customerQuery->where('name', 'LIKE', "%{$search}%");
              });
        });
    }
}