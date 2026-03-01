<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email_verified_at' => $this->email_verified_at,
            'two_factor_enabled' => (bool) $this->two_factor_confirmed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'companies' => CompanyResource::collection($this->whenLoaded('companies')),
        ];
    }
}
