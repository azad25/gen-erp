<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Cart item API resource.
 */
class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cart_id' => $this->cart_id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'total' => $this->getTotal(),
            'display_name' => $this->getDisplayName(),
            'sku' => $this->getSku(),
            'is_available' => $this->isAvailable(),
            'available_stock' => $this->getAvailableStock(),
            'product' => new ProductResource($this->whenLoaded('product')),
            'product_variant' => new ProductVariantResource($this->whenLoaded('productVariant')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}