<?php

namespace App\Domain\Inventory\Events;

use App\Domain\Inventory\Models\Warehouse;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a warehouse is updated.
 */
class WarehouseUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Warehouse $warehouse
    ) {}
}