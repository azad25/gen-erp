<?php

namespace App\Domain\CMS\DTOs;

use App\Domain\CMS\Enums\SiteStatus;

readonly class CreateSiteData
{
    public function __construct(
        public int $companyId,
        public string $name,
        public string $slug,
        public ?string $domain = null,
        public ?string $subdomain = null,
        public ?string $logo = null,
        public ?string $favicon = null,
        public string $primaryColor = '#3B82F6',
        public string $accentColor = '#10B981',
        public string $fontFamily = 'Inter',
        public SiteStatus $status = SiteStatus::DRAFT,
        public ?string $seoTitle = null,
        public ?string $seoDescription = null,
        public ?string $seoImage = null,
        public ?string $googleAnalyticsId = null,
        public ?string $facebookPixelId = null,
        public ?array $settings = null,
    ) {}

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'name' => $this->name,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'subdomain' => $this->subdomain,
            'logo' => $this->logo,
            'favicon' => $this->favicon,
            'primary_color' => $this->primaryColor,
            'accent_color' => $this->accentColor,
            'font_family' => $this->fontFamily,
            'status' => $this->status,
            'seo_title' => $this->seoTitle,
            'seo_description' => $this->seoDescription,
            'seo_image' => $this->seoImage,
            'google_analytics_id' => $this->googleAnalyticsId,
            'facebook_pixel_id' => $this->facebookPixelId,
            'settings' => $this->settings,
        ];
    }
}