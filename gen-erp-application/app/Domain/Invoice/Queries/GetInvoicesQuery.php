<?php

namespace App\Domain\Invoice\Queries;

use App\Domain\Shared\Queries\BaseQuery;

/**
 * Query to get paginated invoices with filters.
 */
class GetInvoicesQuery extends BaseQuery
{
    public function __construct(
        public readonly int $companyId,
        public readonly array $filters = [],
        public readonly int $perPage = 15,
        public readonly int $page = 1
    ) {
        parent::__construct();
    }
}