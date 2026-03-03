<?php

namespace App\Domain\Invoice\Repositories\Eloquent;

use App\Domain\Invoice\Models\Invoice;
use App\Domain\Auth\Models\Company;
use App\Domain\Invoice\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Support\Enums\InvoiceStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    /**
     * Find invoice by ID for the given company.
     */
    public function findByIdForCompany(int $id, Company $company): ?Invoice
    {
        return Invoice::where('company_id', $company->id)
            ->with(['customer', 'items.product'])
            ->find($id);
    }

    /**
     * Get paginated invoices for a company with filters.
     */
    public function paginateForCompany(Company $company, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Invoice::query()
            ->where('company_id', $company->id)
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'LIKE', "%{$search}%")
                      ->orWhere('notes', 'LIKE', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when($filters['customer_id'] ?? null, fn($q, $id) => $q->where('customer_id', $id))
            ->when($filters['from_date'] ?? null, fn($q, $date) => $q->where('invoice_date', '>=', $date))
            ->when($filters['to_date'] ?? null, fn($q, $date) => $q->where('invoice_date', '<=', $date))
            ->with(['customer', 'items.product']) // Always eager load relationships
            ->orderBy('invoice_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a new invoice.
     */
    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    /**
     * Update an existing invoice.
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);
        return $invoice->fresh();
    }

    /**
     * Delete an invoice.
     */
    public function delete(Invoice $invoice): bool
    {
        return $invoice->delete();
    }

    /**
     * Find overdue invoices for a company.
     */
    public function findOverdueForCompany(Company $company): Collection
    {
        return Invoice::where('company_id', $company->id)
            ->where('status', InvoiceStatus::SENT)
            ->where('due_date', '<', now()->startOfDay())
            ->with(['customer'])
            ->get();
    }

    /**
     * Get recent invoices for dashboard.
     */
    public function getRecentForCompany(Company $company, int $limit = 10): Collection
    {
        return Invoice::where('company_id', $company->id)
            ->with(['customer:id,name'])
            ->latest('invoice_date')
            ->limit($limit)
            ->get(['id', 'invoice_number', 'customer_id', 'total_amount', 'status', 'invoice_date']);
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(Invoice $invoice): Invoice
    {
        $invoice->update(['status' => InvoiceStatus::SENT]);
        return $invoice->fresh();
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Invoice $invoice): Invoice
    {
        $invoice->update(['status' => InvoiceStatus::PAID]);
        return $invoice->fresh();
    }
}