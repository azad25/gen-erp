<?php

namespace App\Domain\SalesOrder\Actions;

use App\Domain\SalesOrder\Models\SalesOrder;
use App\Domain\SalesOrder\Events\SalesOrderConfirmed;
use App\Domain\Product\Models\Product;
use App\Domain\Inventory\Services\InventoryService;
use App\Support\Enums\SalesOrderStatus;
use Illuminate\Support\Facades\DB;

/**
 * Confirm the order and reserve stock for each item.
 */
class ConfirmSalesOrderAction
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function execute(SalesOrder $order): void
    {
        throw_if(
            $order->status !== SalesOrderStatus::DRAFT,
            new \InvalidArgumentException('Only draft orders can be confirmed.')
        );

        DB::transaction(function () use ($order): void {
            foreach ($order->items as $item) {
                if ($item->product_id === null) {
                    continue;
                }

                $product = Product::withoutGlobalScopes()->find($item->product_id);
                if ($product === null || ! $product->track_inventory) {
                    continue;
                }

                $this->inventoryService->reserve(
                    $order->warehouse_id,
                    $item->product_id,
                    (float) $item->quantity,
                    $item->variant_id,
                );
            }

            $order->update(['status' => SalesOrderStatus::CONFIRMED]);

            // Fire domain event
            SalesOrderConfirmed::dispatch($order);
        });
    }
}