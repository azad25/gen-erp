<?php

namespace App\Domain\CMS\Services;

use App\Domain\CMS\Contracts\CMSServiceInterface;
use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\Page;
use App\Domain\CMS\Models\Section;
use App\Domain\CMS\DTOs\CreateSiteData;
use App\Domain\CMS\DTOs\UpdateSiteData;
use App\Domain\CMS\DTOs\CreatePageData;
use App\Domain\CMS\DTOs\UpdatePageData;
use App\Domain\CMS\DTOs\CreateSectionData;
use App\Domain\CMS\DTOs\UpdateSectionData;
use App\Domain\CMS\Enums\SiteStatus;
use App\Domain\CMS\Enums\PageStatus;
use App\Domain\CMS\Events\SiteCreated;
use App\Domain\CMS\Events\SitePublished;
use App\Domain\CMS\Events\PageCreated;
use App\Domain\CMS\Events\PagePublished;
use App\Domain\CMS\Events\SectionCreated;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CMSService implements CMSServiceInterface
{
    // ── Site Management ──────────────────────────────────────

    public function getSitesForCompany(int $companyId): Collection
    {
        return Site::where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createSite(CreateSiteData $data): Site
    {
        $site = Site::create($data->toArray());

        event(new SiteCreated($site));

        return $site;
    }

    public function updateSite(int $siteId, UpdateSiteData $data): Site
    {
        $site = Site::findOrFail($siteId);
        $site->update($data->toArray());

        return $site->fresh();
    }

    public function deleteSite(int $siteId): bool
    {
        $site = Site::findOrFail($siteId);
        return $site->delete();
    }

    public function publishSite(int $siteId): Site
    {
        $site = Site::findOrFail($siteId);
        
        $site->update([
            'status' => SiteStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        event(new SitePublished($site));

        return $site->fresh();
    }

    public function unpublishSite(int $siteId): Site
    {
        $site = Site::findOrFail($siteId);
        
        $site->update([
            'status' => SiteStatus::DRAFT,
            'published_at' => null,
        ]);

        return $site->fresh();
    }

    public function findSiteBySubdomain(string $subdomain): ?Site
    {
        return Site::bySubdomain($subdomain)->published()->first();
    }

    public function findSiteByDomain(string $domain): ?Site
    {
        return Site::byDomain($domain)->published()->first();
    }

    // ── Page Management ──────────────────────────────────────

    public function getPagesForSite(int $siteId): Collection
    {
        return Page::where('site_id', $siteId)
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createPage(CreatePageData $data): Page
    {
        // If this is set as homepage, unset other homepages
        if ($data->isHomepage) {
            Page::where('site_id', $data->siteId)
                ->where('is_homepage', true)
                ->update(['is_homepage' => false]);
        }

        $page = Page::create($data->toArray());

        event(new PageCreated($page));

        return $page;
    }

    public function updatePage(int $pageId, UpdatePageData $data): Page
    {
        $page = Page::findOrFail($pageId);

        // If this is set as homepage, unset other homepages
        if ($data->isHomepage === true) {
            Page::where('site_id', $page->site_id)
                ->where('id', '!=', $pageId)
                ->where('is_homepage', true)
                ->update(['is_homepage' => false]);
        }

        $page->update($data->toArray());

        return $page->fresh();
    }

    public function deletePage(int $pageId): bool
    {
        $page = Page::findOrFail($pageId);
        return $page->delete();
    }

    public function publishPage(int $pageId): Page
    {
        $page = Page::findOrFail($pageId);
        
        $page->update([
            'status' => PageStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        event(new PagePublished($page));

        return $page->fresh();
    }

    public function unpublishPage(int $pageId): Page
    {
        $page = Page::findOrFail($pageId);
        
        $page->update([
            'status' => PageStatus::DRAFT,
            'published_at' => null,
        ]);

        return $page->fresh();
    }

    public function setAsHomepage(int $pageId): Page
    {
        $page = Page::findOrFail($pageId);

        // Unset other homepages for this site
        Page::where('site_id', $page->site_id)
            ->where('id', '!=', $pageId)
            ->where('is_homepage', true)
            ->update(['is_homepage' => false]);

        $page->update(['is_homepage' => true]);

        return $page->fresh();
    }

    public function findPageBySlug(int $siteId, string $slug): ?Page
    {
        return Page::where('site_id', $siteId)
            ->bySlug($slug)
            ->published()
            ->first();
    }

    // ── Section Management ───────────────────────────────────

    public function getSectionsForPage(int $pageId): Collection
    {
        return Section::where('page_id', $pageId)
            ->orderBy('sort_order')
            ->get();
    }

    public function createSection(CreateSectionData $data): Section
    {
        // Auto-assign sort order if not provided
        if ($data->sortOrder === 0) {
            $maxOrder = Section::where('page_id', $data->pageId)->max('sort_order') ?? 0;
            $sectionData = $data->toArray();
            $sectionData['sort_order'] = $maxOrder + 1;
        } else {
            $sectionData = $data->toArray();
        }

        $section = Section::create($sectionData);

        event(new SectionCreated($section));

        return $section;
    }

    public function updateSection(int $sectionId, UpdateSectionData $data): Section
    {
        $section = Section::findOrFail($sectionId);
        $section->update($data->toArray());

        return $section->fresh();
    }

    public function deleteSection(int $sectionId): bool
    {
        $section = Section::findOrFail($sectionId);
        return $section->delete();
    }

    public function reorderSections(int $pageId, array $sectionIds): bool
    {
        foreach ($sectionIds as $order => $sectionId) {
            Section::where('id', $sectionId)
                ->where('page_id', $pageId)
                ->update(['sort_order' => $order]);
        }

        return true;
    }

    public function duplicateSection(int $sectionId): Section
    {
        $originalSection = Section::findOrFail($sectionId);
        
        $maxOrder = Section::where('page_id', $originalSection->page_id)->max('sort_order') ?? 0;

        $duplicatedSection = Section::create([
            'page_id' => $originalSection->page_id,
            'type' => $originalSection->type,
            'content' => $originalSection->content,
            'sort_order' => $maxOrder + 1,
            'is_visible' => $originalSection->is_visible,
        ]);

        return $duplicatedSection;
    }

    // ── Utility Methods ──────────────────────────────────────

    public function generateUniqueSlug(int $siteId, string $title, ?int $excludePageId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($siteId, $slug, $excludePageId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function getSiteStatistics(int $siteId): array
    {
        $site = Site::findOrFail($siteId);

        return [
            'total_pages' => $site->pages()->count(),
            'published_pages' => $site->pages()->published()->count(),
            'draft_pages' => $site->pages()->draft()->count(),
            'total_sections' => Section::whereHas('page', function ($query) use ($siteId) {
                $query->where('site_id', $siteId);
            })->count(),
            'total_blog_posts' => $site->blogPosts()->count(),
            'published_posts' => $site->blogPosts()->published()->count(),
            'total_menus' => $site->menus()->count(),
            'last_updated' => $site->pages()->latest('updated_at')->first()?->updated_at,
        ];
    }

    // ── Private Methods ──────────────────────────────────────

    private function slugExists(int $siteId, string $slug, ?int $excludePageId = null): bool
    {
        $query = Page::where('site_id', $siteId)->where('slug', $slug);

        if ($excludePageId) {
            $query->where('id', '!=', $excludePageId);
        }

        return $query->exists();
    }
}