<?php

namespace App\Domain\SalesOrder\Actions;

use App\Domain\SalesOrder\Models\SalesOrder;
use App\Domain\SalesOrder\Events\SalesOrderCancelled;
use App\Domain\Product\Models\Product;
use App\Domain\Inventory\Services\InventoryService;
use App\Support\Enums\SalesOrderStatus;
use Illuminate\Support\Facades\DB;

/**
 * Cancel a sales order and release any stock reservations.
 */
class CancelSalesOrderAction
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function execute(SalesOrder $order): void
    {
        throw_if(
            $order->status === SalesOrderStatus::CANCELLED,
            new \InvalidArgumentException('Order is already cancelled.')
        );

        DB::transaction(function () use ($order): void {
            if ($order->status === SalesOrderStatus::CONFIRMED || $order->status === SalesOrderStatus::PROCESSING) {
                foreach ($order->items as $item) {
                    if ($item->product_id === null) {
                        continue;
                    }

                    $product = Product::withoutGlobalScopes()->find($item->product_id);
                    if ($product === null || ! $product->track_inventory) {
                        continue;
                    }

                    $this->inventoryService->releaseReservation(
                        $order->warehouse_id,
                        $item->product_id,
                        (float) $item->quantity,
                        $item->variant_id,
                    );
                }
            }

            $order->update(['status' => SalesOrderStatus::CANCELLED]);

            // Fire domain event
            SalesOrderCancelled::dispatch($order);
        });
    }
}