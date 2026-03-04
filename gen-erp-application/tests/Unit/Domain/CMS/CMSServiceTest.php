<?php

use App\Domain\CMS\Services\CMSService;
use App\Domain\CMS\DTOs\CreateSiteData;
use App\Domain\CMS\DTOs\UpdateSiteData;
use App\Domain\CMS\DTOs\CreatePageData;
use App\Domain\CMS\DTOs\UpdatePageData;
use App\Domain\CMS\DTOs\CreateSectionData;
use App\Domain\CMS\DTOs\UpdateSectionData;
use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\Page;
use App\Domain\CMS\Models\Section;
use App\Domain\CMS\Enums\SiteStatus;
use App\Domain\CMS\Enums\PageStatus;
use App\Domain\CMS\Enums\SectionType;
use App\Domain\Auth\Models\Company;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->cmsService = app(CMSService::class);
    $this->company = Company::factory()->create();
});

describe('Site Management', function () {
    it('can create a site', function () {
        $uniqueId = uniqid();
        $data = new CreateSiteData(
            companyId: $this->company->id,
            name: 'Test Site',
            slug: 'test-site-' . $uniqueId,
            domain: null,
            subdomain: 'testsite-' . $uniqueId,
            logo: null,
            favicon: null,
            primaryColor: '#3B82F6',
            accentColor: '#10B981',
            fontFamily: 'Inter',
            status: SiteStatus::DRAFT,
            seoTitle: 'Test Site',
            seoDescription: 'A test site',
            seoImage: null,
            googleAnalyticsId: null,
            facebookPixelId: null,
            settings: null,
        );

        $site = $this->cmsService->createSite($data);

        expect($site)->toBeInstanceOf(Site::class);
        expect($site->name)->toBe('Test Site');
        expect($site->slug)->toBe('test-site-' . $uniqueId);
        expect($site->company_id)->toBe($this->company->id);
        expect($site->status)->toBe(SiteStatus::DRAFT);
    });

    it('can update a site', function () {
        $site = Site::factory()->create(['company_id' => $this->company->id]);

        $data = new UpdateSiteData(
            name: 'Updated Site Name',
            slug: null,
            domain: null,
            subdomain: null,
            logo: null,
            favicon: null,
            primaryColor: '#FF0000',
            accentColor: null,
            fontFamily: null,
            seoTitle: 'Updated SEO Title',
            seoDescription: null,
            seoImage: null,
            googleAnalyticsId: null,
            facebookPixelId: null,
            settings: null,
        );

        $updatedSite = $this->cmsService->updateSite($site->id, $data);

        expect($updatedSite->name)->toBe('Updated Site Name');
        expect($updatedSite->primary_color)->toBe('#FF0000');
        expect($updatedSite->seo_title)->toBe('Updated SEO Title');
    });

    it('can publish and unpublish a site', function () {
        $site = Site::factory()->create([
            'company_id' => $this->company->id,
            'status' => SiteStatus::DRAFT,
        ]);

        // Publish
        $publishedSite = $this->cmsService->publishSite($site->id);
        expect($publishedSite->status)->toBe(SiteStatus::PUBLISHED);
        expect($publishedSite->published_at)->not->toBeNull();

        // Unpublish
        $unpublishedSite = $this->cmsService->unpublishSite($site->id);
        expect($unpublishedSite->status)->toBe(SiteStatus::DRAFT);
    });

    it('can find site by domain', function () {
        $uniqueDomain = 'example-' . uniqid() . '.com';
        $site = Site::factory()->create([
            'company_id' => $this->company->id,
            'domain' => $uniqueDomain,
            'status' => SiteStatus::PUBLISHED,
        ]);

        $foundSite = $this->cmsService->findSiteByDomain($uniqueDomain);

        expect($foundSite)->not->toBeNull();
        expect($foundSite->id)->toBe($site->id);
    });

    it('can find site by subdomain', function () {
        $uniqueSubdomain = 'testsite-' . uniqid();
        $site = Site::factory()->create([
            'company_id' => $this->company->id,
            'subdomain' => $uniqueSubdomain,
            'status' => SiteStatus::PUBLISHED,
        ]);

        $foundSite = $this->cmsService->findSiteBySubdomain($uniqueSubdomain);

        expect($foundSite)->not->toBeNull();
        expect($foundSite->id)->toBe($site->id);
    });

    it('can get sites for company', function () {
        Site::factory()->count(3)->create(['company_id' => $this->company->id]);
        Site::factory()->count(2)->create(); // Other company sites

        $sites = $this->cmsService->getSitesForCompany($this->company->id);

        expect($sites)->toHaveCount(3);
    });

    it('can get site statistics', function () {
        $site = Site::factory()->create(['company_id' => $this->company->id]);
        $page = Page::factory()->create(['site_id' => $site->id]);
        Section::factory()->count(3)->create(['page_id' => $page->id]);

        $stats = $this->cmsService->getSiteStatistics($site->id);

        expect($stats)->toHaveKeys(['total_pages', 'published_pages', 'total_sections', 'total_blog_posts']);
        expect($stats['total_pages'])->toBe(1);
        expect($stats['total_sections'])->toBe(3);
    });

    it('can delete a site', function () {
        $site = Site::factory()->create(['company_id' => $this->company->id]);

        $result = $this->cmsService->deleteSite($site->id);

        expect($result)->toBeTrue();
        expect(Site::find($site->id))->toBeNull();
    });
});

describe('Page Management', function () {
    beforeEach(function () {
        $this->site = Site::factory()->create(['company_id' => $this->company->id]);
    });

    it('can create a page', function () {
        $data = new CreatePageData(
            siteId: $this->site->id,
            title: 'About Us',
            slug: 'about-us',
            seoTitle: 'About Our Company',
            seoDescription: 'Learn about our company',
            seoImage: null,
            status: PageStatus::DRAFT,
            isHomepage: false,
            sortOrder: 0,
        );

        $page = $this->cmsService->createPage($data);

        expect($page)->toBeInstanceOf(Page::class);
        expect($page->title)->toBe('About Us');
        expect($page->slug)->toBe('about-us');
        expect($page->site_id)->toBe($this->site->id);
        expect($page->status)->toBe(PageStatus::DRAFT);
    });

    it('can update a page', function () {
        $page = Page::factory()->create(['site_id' => $this->site->id]);

        $data = new UpdatePageData(
            title: 'Updated Page Title',
            slug: null,
            seoTitle: 'Updated SEO Title',
            seoDescription: null,
            seoImage: null,
            isHomepage: null,
            sortOrder: null,
        );

        $updatedPage = $this->cmsService->updatePage($page->id, $data);

        expect($updatedPage->title)->toBe('Updated Page Title');
        expect($updatedPage->seo_title)->toBe('Updated SEO Title');
    });

    it('can publish and unpublish a page', function () {
        $page = Page::factory()->create([
            'site_id' => $this->site->id,
            'status' => PageStatus::DRAFT,
        ]);

        // Publish
        $publishedPage = $this->cmsService->publishPage($page->id);
        expect($publishedPage->status)->toBe(PageStatus::PUBLISHED);
        expect($publishedPage->published_at)->not->toBeNull();

        // Unpublish
        $unpublishedPage = $this->cmsService->unpublishPage($page->id);
        expect($unpublishedPage->status)->toBe(PageStatus::DRAFT);
    });

    it('can set page as homepage', function () {
        $page1 = Page::factory()->create(['site_id' => $this->site->id, 'is_homepage' => true]);
        $page2 = Page::factory()->create(['site_id' => $this->site->id, 'is_homepage' => false]);

        $homepagePage = $this->cmsService->setAsHomepage($page2->id);

        expect($homepagePage->is_homepage)->toBeTrue();
        
        // Check that the previous homepage is no longer homepage
        $page1->refresh();
        expect($page1->is_homepage)->toBeFalse();
    });

    it('can get pages for site', function () {
        Page::factory()->count(3)->create(['site_id' => $this->site->id]);
        Page::factory()->count(2)->create(); // Other site pages

        $pages = $this->cmsService->getPagesForSite($this->site->id);

        expect($pages)->toHaveCount(3);
    });

    it('can delete a page', function () {
        $page = Page::factory()->create(['site_id' => $this->site->id]);

        $result = $this->cmsService->deletePage($page->id);

        expect($result)->toBeTrue();
        expect(Page::find($page->id))->toBeNull();
    });
});

describe('Section Management', function () {
    beforeEach(function () {
        $this->site = Site::factory()->create(['company_id' => $this->company->id]);
        $this->page = Page::factory()->create(['site_id' => $this->site->id]);
    });

    it('can create a section', function () {
        $data = new CreateSectionData(
            pageId: $this->page->id,
            type: SectionType::HERO_BANNER,
            content: ['title' => 'Welcome', 'subtitle' => 'To our site'],
            sortOrder: 0,
            isVisible: true,
        );

        $section = $this->cmsService->createSection($data);

        expect($section)->toBeInstanceOf(Section::class);
        expect($section->type)->toBe(SectionType::HERO_BANNER);
        expect($section->page_id)->toBe($this->page->id);
        expect($section->content['title'])->toBe('Welcome');
    });

    it('can update a section', function () {
        $section = Section::factory()->create(['page_id' => $this->page->id]);

        $data = new UpdateSectionData(
            type: SectionType::TEXT_BLOCK,
            content: ['title' => 'Updated Title', 'text' => 'Updated content'],
            sortOrder: null,
            isVisible: null,
        );

        $updatedSection = $this->cmsService->updateSection($section->id, $data);

        expect($updatedSection->type)->toBe(SectionType::TEXT_BLOCK);
        expect($updatedSection->content['title'])->toBe('Updated Title');
    });

    it('can duplicate a section', function () {
        $section = Section::factory()->create([
            'page_id' => $this->page->id,
            'content' => ['title' => 'Original Section'],
            'sort_order' => 0,
        ]);

        $duplicatedSection = $this->cmsService->duplicateSection($section->id);

        expect($duplicatedSection)->toBeInstanceOf(Section::class);
        expect($duplicatedSection->id)->not->toBe($section->id);
        expect($duplicatedSection->content['title'])->toBe('Original Section');
        expect($duplicatedSection->sort_order)->toBe(1); // Should be next in order
    });

    it('can reorder sections', function () {
        $section1 = Section::factory()->create(['page_id' => $this->page->id, 'sort_order' => 0]);
        $section2 = Section::factory()->create(['page_id' => $this->page->id, 'sort_order' => 1]);
        $section3 = Section::factory()->create(['page_id' => $this->page->id, 'sort_order' => 2]);

        $newOrder = [$section3->id, $section1->id, $section2->id];

        $this->cmsService->reorderSections($this->page->id, $newOrder);

        $section1->refresh();
        $section2->refresh();
        $section3->refresh();

        expect($section3->sort_order)->toBe(0);
        expect($section1->sort_order)->toBe(1);
        expect($section2->sort_order)->toBe(2);
    });

    it('can get sections for page', function () {
        Section::factory()->count(3)->create(['page_id' => $this->page->id]);
        Section::factory()->count(2)->create(); // Other page sections

        $sections = $this->cmsService->getSectionsForPage($this->page->id);

        expect($sections)->toHaveCount(3);
    });

    it('can delete a section', function () {
        $section = Section::factory()->create(['page_id' => $this->page->id]);

        $result = $this->cmsService->deleteSection($section->id);

        expect($result)->toBeTrue();
        expect(Section::find($section->id))->toBeNull();
    });
});

describe('Utility Methods', function () {
    it('can generate unique slug', function () {
        $site = Site::factory()->create(['company_id' => $this->company->id]);
        
        // Create a page with existing slug
        Page::factory()->create(['site_id' => $site->id, 'slug' => 'about-us']);

        $slug = $this->cmsService->generateUniqueSlug($site->id, 'About Us');

        expect($slug)->toBe('about-us-1');
    });

    it('generates base slug when no conflicts', function () {
        $site = Site::factory()->create(['company_id' => $this->company->id]);

        $slug = $this->cmsService->generateUniqueSlug($site->id, 'Contact Us');

        expect($slug)->toBe('contact-us');
    });
});