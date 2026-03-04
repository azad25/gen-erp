<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
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
            'page_id' => $this->page_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'type_category' => $this->type->category(),
            'type_icon' => $this->type->icon(),
            'display_title' => $this->getDisplayTitle(),
            'icon' => $this->getIcon(),
            'content' => $this->content,
            'sort_order' => $this->sort_order,
            'is_visible' => $this->is_visible,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
