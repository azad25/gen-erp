<?php

namespace App\Domain\CMS\Contracts;

use App\Domain\CMS\Models\Site;

interface PublicSiteServiceInterface
{
    /**
     * Find site by domain or subdomain.
     */
    public function findSiteByTenant(string $tenant): ?Site;

    /**
     * Get site with all public data for rendering.
     */
    public function getSiteData(string $tenant): ?array;

    /**
     * Get page by slug for a site.
     */
    public function getPageBySlug(string $tenant, string $slug): ?array;

    /**
     * Get homepage for a site.
     */
    public function getHomepage(string $tenant): ?array;

    /**
     * Get all published pages for a site.
     */
    public function getSitePages(string $tenant): array;

    /**
     * Get site menus.
     */
    public function getSiteMenus(int $siteId): array;

    /**
     * Get blog posts for a site.
     */
    public function getBlogPosts(string $tenant, ?int $categoryId = null, int $perPage = 10): array;

    /**
     * Get single blog post by slug.
     */
    public function getBlogPost(string $tenant, string $slug): ?array;

    /**
     * Search pages and blog posts.
     */
    public function search(string $tenant, string $query, int $perPage = 10): array;
}