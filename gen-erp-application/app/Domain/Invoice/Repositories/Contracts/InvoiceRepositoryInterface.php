<?php

namespace App\Domain\Invoice\Repositories\Contracts;

use App\Domain\Invoice\Models\Invoice;
use App\Domain\Auth\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface InvoiceRepositoryInterface
{
    /**
     * Find invoice by ID for the given company.
     */
    public function findByIdForCompany(int $id, Company $company): ?Invoice;

    /**
     * Get paginated invoices for a company with filters.
     */
    public function paginateForCompany(Company $company, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new invoice.
     */
    public function create(array $data): Invoice;

    /**
     * Update an existing invoice.
     */
    public function update(Invoice $invoice, array $data): Invoice;

    /**
     * Delete an invoice.
     */
    public function delete(Invoice $invoice): bool;

    /**
     * Find overdue invoices for a company.
     */
    public function findOverdueForCompany(Company $company): Collection;

    /**
     * Get recent invoices for dashboard.
     */
    public function getRecentForCompany(Company $company, int $limit = 10): Collection;

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(Invoice $invoice): Invoice;

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Invoice $invoice): Invoice;
}