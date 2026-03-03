<?php

namespace App\Domain\Invoice\Queries;

use App\Domain\Shared\Queries\BaseQuery;

/**
 * Query to get a specific invoice by ID.
 */
class GetInvoiceQuery extends BaseQuery
{
    public function __construct(
        public readonly int $invoiceId,
        public readonly int $companyId
    ) {
        parent::__construct();
    }
}