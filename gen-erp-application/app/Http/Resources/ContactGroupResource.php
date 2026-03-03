<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Customer\Models\ContactGroup
 */
class ContactGroupResource extends JsonResource
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
            'description' => $this->description,
            'customers' => CustomerResource::collection($this->whenLoaded('customers')),
            'suppliers' => SupplierResource::collection($this->whenLoaded('suppliers')),
            'customers_count' => $this->whenCounted('customers'),
            'suppliers_count' => $this->whenCounted('suppliers'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}