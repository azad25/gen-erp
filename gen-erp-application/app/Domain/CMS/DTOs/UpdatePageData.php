<?php

namespace App\Domain\CMS\DTOs;

use App\Domain\CMS\Enums\PageStatus;

readonly class UpdatePageData
{
    public function __construct(
        public ?string $title = null,
        public ?string $slug = null,
        public ?string $seoTitle = null,
        public ?string $seoDescription = null,
        public ?string $seoImage = null,
        public ?PageStatus $status = null,
        public ?bool $isHomepage = null,
        public ?int $sortOrder = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'slug' => $this->slug,
            'seo_title' => $this->seoTitle,
            'seo_description' => $this->seoDescription,
            'seo_image' => $this->seoImage,
            'status' => $this->status,
            'is_homepage' => $this->isHomepage,
            'sort_order' => $this->sortOrder,
        ], fn($value) => $value !== null);
    }
}