<?php

namespace App\Domain\Invoice\Handlers;

use App\Domain\Shared\Queries\QueryHandlerInterface;
use App\Domain\Shared\Queries\QueryInterface;
use App\Domain\Invoice\Queries\GetInvoiceQuery;
use App\Domain\Invoice\Models\Invoice;

/**
 * Handler for GetInvoiceQuery.
 */
class GetInvoiceQueryHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): ?Invoice
    {
        if (!$query instanceof GetInvoiceQuery) {
            throw new \InvalidArgumentException('Expected GetInvoiceQuery');
        }

        return Invoice::withoutGlobalScopes()
            ->where('id', $query->invoiceId)
            ->where('company_id', $query->companyId)
            ->with(['customer', 'items.product'])
            ->first();
    }
}