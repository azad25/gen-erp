<?php

namespace App\Domain\SalesOrder\Listeners;

use App\Domain\SalesOrder\Events\SalesOrderConfirmed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send notification when a sales order is confirmed.
 */
class NotifyOrderConfirmation implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(SalesOrderConfirmed $event): void
    {
        $order = $event->salesOrder;

        // Log the order confirmation
        Log::info('Sales order confirmed', [
            'order_id' => $order->id,
            'reference_number' => $order->reference_number,
            'customer_id' => $order->customer_id,
            'total_amount' => $order->total_amount,
        ]);

        // TODO: Implement actual notification logic
        // - Send confirmation email to customer
        // - Notify warehouse team
        // - Update inventory alerts
        // - Trigger fulfillment workflow
    }
}