<?php

namespace App\Domain\Invoice\Handlers;

use App\Domain\Shared\Queries\QueryHandlerInterface;
use App\Domain\Shared\Queries\QueryInterface;
use App\Domain\Invoice\Queries\GetInvoicesQuery;
use App\Domain\Invoice\Services\InvoiceService;
use App\Domain\Auth\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handler for GetInvoicesQuery.
 */
class GetInvoicesQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly InvoiceService $invoiceService
    ) {}

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        if (!$query instanceof GetInvoicesQuery) {
            throw new \InvalidArgumentException('Expected GetInvoicesQuery');
        }

        $company = Company::withoutGlobalScopes()->findOrFail($query->companyId);

        return $this->invoiceService->paginateInvoices($company, $query->filters, $query->perPage);
    }
}