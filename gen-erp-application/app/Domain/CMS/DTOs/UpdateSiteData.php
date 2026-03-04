<?php

namespace App\Domain\CMS\DTOs;

use App\Domain\CMS\Enums\SiteStatus;

readonly class UpdateSiteData
{
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $domain = null,
        public ?string $subdomain = null,
        public ?string $logo = null,
        public ?string $favicon = null,
        public ?string $primaryColor = null,
        public ?string $accentColor = null,
        public ?string $fontFamily = null,
        public ?SiteStatus $status = null,
        public ?string $seoTitle = null,
        public ?string $seoDescription = null,
        public ?string $seoImage = null,
        public ?string $googleAnalyticsId = null,
        public ?string $facebookPixelId = null,
        public ?array $settings = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
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
        ], fn($value) => $value !== null);
    }
}