<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'name' => $this->name,
            'sku' => $this->sku,
            'slug' => $this->slug,
            'product_type' => $this->product_type,
            'description' => $this->description,
            'selling_price' => $this->selling_price,
            'purchase_price' => $this->purchase_price,
            'unit' => $this->unit,
            'track_inventory' => $this->track_inventory,
            'is_active' => $this->is_active,
            'reorder_level' => $this->reorder_level,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'tax_group' => $this->whenLoaded('taxGroup', function () {
                return [
                    'id' => $this->taxGroup->id,
                    'name' => $this->taxGroup->name,
                    'rate' => $this->taxGroup->rate,
                ];
            }),
            'variants' => $this->whenLoaded('variants'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}