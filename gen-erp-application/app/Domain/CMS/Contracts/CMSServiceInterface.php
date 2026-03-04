<?php

namespace App\Domain\CMS\Contracts;

use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\Page;
use App\Domain\CMS\Models\Section;
use App\Domain\CMS\DTOs\CreateSiteData;
use App\Domain\CMS\DTOs\UpdateSiteData;
use App\Domain\CMS\DTOs\CreatePageData;
use App\Domain\CMS\DTOs\UpdatePageData;
use App\Domain\CMS\DTOs\CreateSectionData;
use App\Domain\CMS\DTOs\UpdateSectionData;
use Illuminate\Database\Eloquent\Collection;

interface CMSServiceInterface
{
    // ── Site Management ──────────────────────────────────────

    /**
     * Get all sites for a company.
     *
     * @return Collection<int, Site>
     */
    public function getSitesForCompany(int $companyId): Collection;

    /**
     * Create a new site.
     */
    public function createSite(CreateSiteData $data): Site;

    /**
     * Update an existing site.
     */
    public function updateSite(int $siteId, UpdateSiteData $data): Site;

    /**
     * Delete a site.
     */
    public function deleteSite(int $siteId): bool;

    /**
     * Publish a site.
     */
    public function publishSite(int $siteId): Site;

    /**
     * Unpublish a site.
     */
    public function unpublishSite(int $siteId): Site;

    /**
     * Find site by subdomain.
     */
    public function findSiteBySubdomain(string $subdomain): ?Site;

    /**
     * Find site by custom domain.
     */
    public function findSiteByDomain(string $domain): ?Site;

    // ── Page Management ──────────────────────────────────────

    /**
     * Get all pages for a site.
     *
     * @return Collection<int, Page>
     */
    public function getPagesForSite(int $siteId): Collection;

    /**
     * Create a new page.
     */
    public function createPage(CreatePageData $data): Page;

    /**
     * Update an existing page.
     */
    public function updatePage(int $pageId, UpdatePageData $data): Page;

    /**
     * Delete a page.
     */
    public function deletePage(int $pageId): bool;

    /**
     * Publish a page.
     */
    public function publishPage(int $pageId): Page;

    /**
     * Unpublish a page.
     */
    public function unpublishPage(int $pageId): Page;

    /**
     * Set page as homepage.
     */
    public function setAsHomepage(int $pageId): Page;

    /**
     * Find page by slug within a site.
     */
    public function findPageBySlug(int $siteId, string $slug): ?Page;

    // ── Section Management ───────────────────────────────────

    /**
     * Get all sections for a page.
     *
     * @return Collection<int, Section>
     */
    public function getSectionsForPage(int $pageId): Collection;

    /**
     * Create a new section.
     */
    public function createSection(CreateSectionData $data): Section;

    /**
     * Update an existing section.
     */
    public function updateSection(int $sectionId, UpdateSectionData $data): Section;

    /**
     * Delete a section.
     */
    public function deleteSection(int $sectionId): bool;

    /**
     * Reorder sections for a page.
     *
     * @param array<int, int> $sectionIds Array of section IDs in new order
     */
    public function reorderSections(int $pageId, array $sectionIds): bool;

    /**
     * Duplicate a section.
     */
    public function duplicateSection(int $sectionId): Section;

    // ── Utility Methods ──────────────────────────────────────

    /**
     * Generate unique slug for a page within a site.
     */
    public function generateUniqueSlug(int $siteId, string $title, ?int $excludePageId = null): string;

    /**
     * Get site statistics.
     */
    public function getSiteStatistics(int $siteId): array;
}