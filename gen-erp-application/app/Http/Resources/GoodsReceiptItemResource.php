<?php

namespace App\Http\Resources;

use App\Domain\Purchase\Models\GoodsReceiptItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GoodsReceiptItem
 */
class GoodsReceiptItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity_received' => $this->quantity_received,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'expiry_date' => $this->expiry_date,
            'batch_number' => $this->batch_number,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'purchase_order_item' => new PurchaseOrderItemResource($this->whenLoaded('purchaseOrderItem')),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}