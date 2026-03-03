<?php

namespace App\Domain\Invoice\Commands;

use App\Domain\Shared\Commands\BaseCommand;

/**
 * Command to create a new invoice.
 */
class CreateInvoiceCommand extends BaseCommand
{
    public function __construct(
        public readonly int $companyId,
        public readonly int $customerId,
        public readonly ?int $warehouseId,
        public readonly string $invoiceDate,
        public readonly ?string $dueDate,
        public readonly ?string $notes,
        public readonly array $items,
        ?int $initiatedBy = null
    ) {
        parent::__construct($initiatedBy);
    }
}