<?php

namespace App\Domain\SalesOrder\Events;

use App\Domain\SalesOrder\Models\SalesOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a sales order is confirmed.
 */
class SalesOrderConfirmed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SalesOrder $salesOrder,
    ) {}
}