<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Product\Models\TaxGroup
 */
class TaxGroupResource extends JsonResource
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
            'rate' => $this->rate,
            'rate_basis_points' => $this->rate_basis_points,
            'type' => $this->type,
            'is_compound' => $this->is_compound,
            'description' => $this->description,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'formatted_rate' => $this->formattedRate(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}