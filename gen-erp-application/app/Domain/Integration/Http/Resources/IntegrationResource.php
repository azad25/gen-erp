<?php

namespace App\Domain\Integration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'description' => $this->description,
            'logo_path' => $this->logo_path,
            'tier' => $this->tier->value,
            'tier_label' => $this->tier->label(),
            'min_plan' => $this->min_plan,
            'config_schema' => $this->config_schema,
            'capabilities' => $this->capabilities,
            'is_active' => $this->is_active,
            'is_official' => $this->is_official,
            'version' => $this->version,
            'author' => $this->author,
            'author_url' => $this->author_url,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
