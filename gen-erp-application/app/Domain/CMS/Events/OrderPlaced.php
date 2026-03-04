<?php

namespace App\Domain\CMS\Events;

use App\Domain\CMS\Models\PublicOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an order is placed.
 */
class OrderPlaced
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly PublicOrder $order
    ) {}
}