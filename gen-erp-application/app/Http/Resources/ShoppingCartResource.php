<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shopping cart API resource.
 */
class ShoppingCartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'session_id' => $this->session_id,
            'customer_id' => $this->customer_id,
            'expires_at' => $this->expires_at?->toISOString(),
            'item_count' => $this->getItemCount(),
            'totals' => $this->getTotals(),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}