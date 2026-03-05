<?php

namespace App\Domain\Integration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyIntegrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'integration' => new IntegrationResource($this->whenLoaded('integration')),
            'config' => $this->config,
            'field_maps' => $this->field_maps,
            'status' => $this->status,
            'last_sync_at' => $this->last_sync_at?->toISOString(),
            'last_error' => $this->last_error,
            'installed_at' => $this->installed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
