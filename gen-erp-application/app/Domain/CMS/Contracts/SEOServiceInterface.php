<?php

namespace App\Domain\CMS\Contracts;

use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\Page;
use App\Domain\CMS\Models\BlogPost;

interface SEOServiceInterface
{
    /**
     * Generate sitemap XML for a site.
     */
    public function generateSitemap(Site $site): string;

    /**
     * Generate robots.txt for a site.
     */
    public function generateRobotsTxt(Site $site): string;

    /**
     * Generate structured data for a page.
     */
    public function generateStructuredData(Site $site, ?Page $page = null, ?BlogPost $blogPost = null): array;

    /**
     * Get SEO analysis for a site.
     */
    public function analyzeSEO(Site $site): array;

    /**
     * Generate meta tags for a page.
     */
    public function generateMetaTags(Site $site, ?Page $page = null, ?BlogPost $blogPost = null): array;
}