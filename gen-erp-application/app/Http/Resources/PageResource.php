<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
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
            'site_id' => $this->site_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'url' => $this->getUrl(),
            'seo' => [
                'title' => $this->getSeoTitle(),
                'description' => $this->getSeoDescription(),
                'image' => $this->seo_image,
            ],
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'is_homepage' => $this->is_homepage,
            'is_published' => $this->isPublished(),
            'is_scheduled' => $this->isScheduled(),
            'sort_order' => $this->sort_order,
            'published_at' => $this->published_at?->toISOString(),
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships (when loaded)
            'site' => new SiteResource($this->whenLoaded('site')),
            'sections' => SectionResource::collection($this->whenLoaded('sections')),
        ];
    }
}
