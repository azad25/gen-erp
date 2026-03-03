<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\DTOs\UpdateWarehouseData;
use App\Domain\Inventory\Events\WarehouseUpdated;
use App\Domain\Inventory\Models\Warehouse;

/**
 * Action for updating a warehouse.
 */
class UpdateWarehouseAction
{
    public function execute(Warehouse $warehouse, UpdateWarehouseData $data): Warehouse
    {
        $warehouse->update($data->toArray());

        event(new WarehouseUpdated($warehouse));

        return $warehouse->fresh();
    }
}