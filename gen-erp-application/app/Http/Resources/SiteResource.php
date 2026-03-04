<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteResource extends JsonResource
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
            'company_id' => $this->company_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'subdomain' => $this->subdomain,
            'url' => $this->getUrl(),
            'logo' => $this->logo,
            'favicon' => $this->favicon,
            'primary_color' => $this->primary_color,
            'accent_color' => $this->accent_color,
            'font_family' => $this->font_family,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'is_published' => $this->isPublished(),
            'seo' => [
                'title' => $this->seo_title,
                'description' => $this->seo_description,
                'image' => $this->seo_image,
            ],
            'analytics' => [
                'google_analytics_id' => $this->google_analytics_id,
                'facebook_pixel_id' => $this->facebook_pixel_id,
            ],
            'settings' => $this->settings,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships (when loaded)
            'pages' => PageResource::collection($this->whenLoaded('pages')),
            'menus' => MenuResource::collection($this->whenLoaded('menus')),
            'blog_posts' => BlogPostResource::collection($this->whenLoaded('blogPosts')),
            'blog_categories' => BlogCategoryResource::collection($this->whenLoaded('blogCategories')),
        ];
    }
}
