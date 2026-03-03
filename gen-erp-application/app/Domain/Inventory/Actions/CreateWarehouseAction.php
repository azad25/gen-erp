<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\DTOs\CreateWarehouseData;
use App\Domain\Inventory\Events\WarehouseCreated;
use App\Domain\Inventory\Models\Warehouse;

/**
 * Action for creating a new warehouse.
 */
class CreateWarehouseAction
{
    public function execute(CreateWarehouseData $data): Warehouse
    {
        $warehouse = Warehouse::create($data->toArray());

        event(new WarehouseCreated($warehouse));

        return $warehouse;
    }
}