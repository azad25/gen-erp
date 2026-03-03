<?php

namespace App\Http\Resources;

use App\Domain\Purchase\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PurchaseOrder
 */
class PurchaseOrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'order_date' => $this->order_date,
            'expected_delivery_date' => $this->expected_delivery_date,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'notes' => $this->notes,
            'terms_and_conditions' => $this->terms_and_conditions,
            'custom_fields' => $this->custom_fields,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'goods_receipts' => GoodsReceiptResource::collection($this->whenLoaded('goodsReceipts')),
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}