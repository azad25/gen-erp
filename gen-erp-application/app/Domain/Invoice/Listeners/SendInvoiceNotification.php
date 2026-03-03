<?php

namespace App\Domain\Invoice\Listeners;

use App\Domain\Invoice\Events\InvoiceSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send notification when an invoice is sent.
 */
class SendInvoiceNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(InvoiceSent $event): void
    {
        $invoice = $event->invoice;

        // Log the invoice sent event
        Log::info('Invoice sent', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'customer_id' => $invoice->customer_id,
            'total_amount' => $invoice->total_amount,
        ]);

        // TODO: Implement actual notification logic
        // - Send email to customer
        // - Send SMS notification
        // - Update dashboard notifications
        // - Trigger webhook if configured
    }
}