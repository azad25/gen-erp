<?php

namespace App\Domain\Invoice\Handlers;

use App\Domain\Shared\Commands\CommandHandlerInterface;
use App\Domain\Shared\Commands\CommandInterface;
use App\Domain\Invoice\Commands\CreateInvoiceCommand;
use App\Domain\Invoice\Services\InvoiceService;
use App\Domain\Auth\Models\Company;
use App\Domain\Invoice\Models\Invoice;

/**
 * Handler for CreateInvoiceCommand.
 */
class CreateInvoiceCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly InvoiceService $invoiceService
    ) {}

    public function handle(CommandInterface $command): Invoice
    {
        if (!$command instanceof CreateInvoiceCommand) {
            throw new \InvalidArgumentException('Expected CreateInvoiceCommand');
        }

        $company = Company::withoutGlobalScopes()->findOrFail($command->companyId);

        $data = [
            'customer_id' => $command->customerId,
            'warehouse_id' => $command->warehouseId,
            'invoice_date' => $command->invoiceDate,
            'due_date' => $command->dueDate,
            'notes' => $command->notes,
        ];

        return $this->invoiceService->createInvoice($company, $data, $command->items);
    }
}