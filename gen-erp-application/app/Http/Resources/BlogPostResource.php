<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogPostResource extends JsonResource
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
            'category_id' => $this->category_id,
            'author_id' => $this->author_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'url' => $this->getUrl(),
            'excerpt' => $this->getExcerpt(),
            'content' => $this->content,
            'featured_image' => $this->featured_image,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_published' => $this->isPublished(),
            'views_count' => $this->views_count,
            'reading_time' => $this->getReadingTime(),
            'published_at' => $this->published_at?->toISOString(),
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships
            'category' => new BlogCategoryResource($this->whenLoaded('category')),
            'author' => $this->whenLoaded('author', fn() => [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ]),
        ];
    }
}
