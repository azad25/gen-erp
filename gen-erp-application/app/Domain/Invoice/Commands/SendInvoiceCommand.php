<?php

namespace App\Domain\Invoice\Commands;

use App\Domain\Shared\Commands\BaseCommand;

/**
 * Command to send an invoice.
 */
class SendInvoiceCommand extends BaseCommand
{
    public function __construct(
        public readonly int $invoiceId,
        ?int $initiatedBy = null
    ) {
        parent::__construct($initiatedBy);
    }
}