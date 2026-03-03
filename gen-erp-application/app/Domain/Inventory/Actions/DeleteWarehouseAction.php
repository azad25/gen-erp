<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Events\WarehouseDeleted;
use App\Domain\Inventory\Models\Warehouse;

/**
 * Action for deleting a warehouse.
 */
class DeleteWarehouseAction
{
    public function execute(Warehouse $warehouse): void
    {
        $warehouseData = $warehouse->toArray();
        
        $warehouse->delete();

        event(new WarehouseDeleted($warehouseData));
    }
}