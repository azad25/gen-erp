<?php

namespace App\Http\Resources;

use App\Domain\Purchase\Models\GoodsReceipt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GoodsReceipt
 */
class GoodsReceiptResource extends JsonResource
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
            'receipt_number' => $this->receipt_number,
            'receipt_date' => $this->receipt_date,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'purchase_order' => new PurchaseOrderResource($this->whenLoaded('purchaseOrder')),
            'items' => GoodsReceiptItemResource::collection($this->whenLoaded('items')),
        ];
    }
}