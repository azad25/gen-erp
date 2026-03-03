<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'district' => $this->district,
            'credit_limit' => $this->credit_limit,
            'credit_days' => $this->credit_days,
            'opening_balance' => $this->opening_balance,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'contact_group' => $this->whenLoaded('contactGroup', function () {
                return [
                    'id' => $this->contactGroup->id,
                    'name' => $this->contactGroup->name,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}