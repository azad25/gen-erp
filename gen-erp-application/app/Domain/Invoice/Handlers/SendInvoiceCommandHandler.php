<?php

namespace App\Domain\Invoice\Handlers;

use App\Domain\Shared\Commands\CommandHandlerInterface;
use App\Domain\Shared\Commands\CommandInterface;
use App\Domain\Invoice\Commands\SendInvoiceCommand;
use App\Domain\Invoice\Actions\SendInvoiceAction;
use App\Domain\Invoice\Models\Invoice;

/**
 * Handler for SendInvoiceCommand.
 */
class SendInvoiceCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly SendInvoiceAction $sendInvoiceAction
    ) {}

    public function handle(CommandInterface $command): void
    {
        if (!$command instanceof SendInvoiceCommand) {
            throw new \InvalidArgumentException('Expected SendInvoiceCommand');
        }

        $invoice = Invoice::withoutGlobalScopes()->findOrFail($command->invoiceId);
        
        $this->sendInvoiceAction->execute($invoice);
    }
}