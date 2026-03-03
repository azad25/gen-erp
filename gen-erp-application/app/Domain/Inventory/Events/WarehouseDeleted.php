<?php

namespace App\Domain\Inventory\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a warehouse is deleted.
 */
class WarehouseDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly array $warehouseData
    ) {}
}