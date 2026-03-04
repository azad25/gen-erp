<?php

namespace App\Domain\CMS\DTOs;

use App\Domain\CMS\Enums\PageStatus;

readonly class CreatePageData
{
    public function __construct(
        public int $siteId,
        public string $title,
        public string $slug,
        public ?string $seoTitle = null,
        public ?string $seoDescription = null,
        public ?string $seoImage = null,
        public PageStatus $status = PageStatus::DRAFT,
        public bool $isHomepage = false,
        public int $sortOrder = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'site_id' => $this->siteId,
            'title' => $this->title,
            'slug' => $this->slug,
            'seo_title' => $this->seoTitle,
            'seo_description' => $this->seoDescription,
            'seo_image' => $this->seoImage,
            'status' => $this->status,
            'is_homepage' => $this->isHomepage,
            'sort_order' => $this->sortOrder,
        ];
    }
}