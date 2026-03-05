<?php

use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\Page;
use App\Domain\CMS\Models\Section;
use App\Domain\CMS\Enums\SiteStatus;
use App\Domain\CMS\Enums\PageStatus;
use App\Domain\CMS\Enums\SectionType;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Models\Company;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->user->companies()->attach($this->company->id, ['role' => 'admin']);
    $this->user->update(['current_company_id' => $this->company->id]);
    
    $this->actingAs($this->user);
});

describe('CMS Sites', function () {
    it('can list sites for company', function () {
        Site::factory()->count(3)->create(['company_id' => $this->company->id]);
        Site::factory()->count(2)->create(); // Other company sites

        $response = $this->getJson('/api/v1/cms/sites');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('can create a new site', function () {
        $siteData = [
            'name' => 'Test Site',
            'slug' => 'test-site',
            'subdomain' => 'testsite',
            'primary_color' => '#3B82F6',
            'accent_color' => '#10B981',
            'seo_title' => 'Test Site - Welcome',
            'seo_description' => 'This is a test site for our business',
        ];

        $response = $this->postJson('/api/v1/cms/sites', $siteData);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Test Site')
            ->assertJsonPath('data.slug', 'test-site')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('cms_sites', [
            'company_id' => $this->company->id,
            'name' => 'Test Site',
            'slug' => 'test-site',
            'status' => SiteStatus::DRAFT->value,
        ]);
    });

    it('can update a site', function () {
        $site = Site::factory()->create(['company_id' => $this->company->id]);

        $updateData = [
            'name' => 'Updated Site Name',
            'primary_color' => '#FF0000',
        ];

        $response = $this->putJson("/api/v1/cms/sites/{$site->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Site Name')
            ->assertJsonPath('data.primary_color', '#FF0000');
    });

    it('can publish and unpublish a site', function () {
        $site = Site::factory()->create([
            'company_id' => $this->company->id,
            'status' => SiteStatus::DRAFT,
        ]);

        // Publish
        $response = $this->postJson("/api/v1/cms/sites/{$site->id}/publish");
        $response->assertOk()
            ->assertJsonPath('data.status', 'published');

        // Unpublish
        $response = $this->postJson("/api/v1/cms/sites/{$site->id}/unpublish");
        $response->assertOk()
            ->assertJsonPath('data.status', 'draft');
    });

    it('can get site statistics', function () {
        $site = Site::factory()->create(['company_id' => $this->company->id]);
        
        // Create some pages and sections
        $page = Page::factory()->create(['site_id' => $site->id]);
        Section::factory()->count(3)->create(['page_id' => $page->id]);

        $response = $this->getJson("/api/v1/cms/sites/{$site->id}/statistics");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_pages',
                    'published_pages',
                    'total_sections',
                    'total_blog_posts',
                ]
            ]);
    });

    it('can delete a site', function () {
        $site = Site::factory()->create(['company_id' => $this->company->id]);

        $response = $this->deleteJson("/api/v1/cms/sites/{$site->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('cms_sites', ['id' => $site->id]);
    });
});

describe('CMS Pages', function () {
    beforeEach(function () {
        $this->site = Site::factory()->create(['company_id' => $this->company->id]);
    });

    it('can list pages for a site', function () {
        Page::factory()->count(3)->create(['site_id' => $this->site->id]);
        Page::factory()->count(2)->create(); // Other site pages

        $response = $this->getJson("/api/v1/cms/pages?site_id={$this->site->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('can create a new page', function () {
        $pageData = [
            'site_id' => $this->site->id,
            'title' => 'About Us',
            'slug' => 'about-us',
            'seo_title' => 'About Our Company',
            'seo_description' => 'Learn more about our company and mission',
        ];

        $response = $this->postJson('/api/v1/cms/pages', $pageData);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'About Us')
            ->assertJsonPath('data.slug', 'about-us')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('cms_pages', [
            'site_id' => $this->site->id,
            'title' => 'About Us',
            'slug' => 'about-us',
            'status' => PageStatus::DRAFT->value,
        ]);
    });

    it('can update a page', function () {
        $page = Page::factory()->create(['site_id' => $this->site->id]);

        $updateData = [
            'title' => 'Updated Page Title',
            'seo_title' => 'Updated SEO Title',
        ];

        $response = $this->putJson("/api/v1/cms/pages/{$page->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Page Title')
            ->assertJsonPath('data.seo.title', 'Updated SEO Title');
    });

    it('can publish and unpublish a page', function () {
        $page = Page::factory()->create([
            'site_id' => $this->site->id,
            'status' => PageStatus::DRAFT,
        ]);

        // Publish
        $response = $this->postJson("/api/v1/cms/pages/{$page->id}/publish");
        $response->assertOk()
            ->assertJsonPath('data.status', 'published');

        // Unpublish
        $response = $this->postJson("/api/v1/cms/pages/{$page->id}/unpublish");
        $response->assertOk()
            ->assertJsonPath('data.status', 'draft');
    });

    it('can set page as homepage', function () {
        $page = Page::factory()->create(['site_id' => $this->site->id]);

        $response = $this->postJson("/api/v1/cms/pages/{$page->id}/set-homepage");

        $response->assertOk()
            ->assertJsonPath('data.is_homepage', true);

        $this->assertDatabaseHas('cms_pages', [
            'id' => $page->id,
            'is_homepage' => true,
        ]);
    });

    it('can delete a page', function () {
        $page = Page::factory()->create(['site_id' => $this->site->id]);

        $response = $this->deleteJson("/api/v1/cms/pages/{$page->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('cms_pages', ['id' => $page->id]);
    });
});

describe('CMS Sections', function () {
    beforeEach(function () {
        $this->site = Site::factory()->create(['company_id' => $this->company->id]);
        $this->page = Page::factory()->create(['site_id' => $this->site->id]);
    });

    it('can list sections for a page', function () {
        Section::factory()->count(3)->create(['page_id' => $this->page->id]);
        Section::factory()->count(2)->create(); // Other page sections

        $response = $this->getJson("/api/v1/cms/sections?page_id={$this->page->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('can create a new section', function () {
        $sectionData = [
            'page_id' => $this->page->id,
            'type' => SectionType::HERO_BANNER->value,
            'content' => [
                'title' => 'Welcome to Our Site',
                'subtitle' => 'We provide excellent services',
                'button_text' => 'Learn More',
            ],
            'sort_order' => 0,
        ];

        $response = $this->postJson('/api/v1/cms/sections', $sectionData);

        $response->assertCreated()
            ->assertJsonPath('data.type', SectionType::HERO_BANNER->value)
            ->assertJsonPath('data.content.title', 'Welcome to Our Site');

        $this->assertDatabaseHas('cms_sections', [
            'page_id' => $this->page->id,
            'type' => SectionType::HERO_BANNER->value,
        ]);
    });

    it('can update a section', function () {
        $section = Section::factory()->create([
            'page_id' => $this->page->id,
            'type' => SectionType::TEXT_BLOCK->value,
        ]);

        $updateData = [
            'content' => [
                'title' => 'Updated Section Title',
                'text' => 'Updated section content',
            ],
        ];

        $response = $this->putJson("/api/v1/cms/sections/{$section->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('data.content.title', 'Updated Section Title');
    });

    it('can duplicate a section', function () {
        $section = Section::factory()->create([
            'page_id' => $this->page->id,
            'type' => SectionType::TEXT_BLOCK->value,
            'content' => ['title' => 'Original Section'],
        ]);

        $response = $this->postJson("/api/v1/cms/sections/{$section->id}/duplicate");

        $response->assertCreated();
        
        $this->assertEquals(2, Section::where('page_id', $this->page->id)->count());
    });

    it('can reorder sections', function () {
        $section1 = Section::factory()->create(['page_id' => $this->page->id, 'sort_order' => 0]);
        $section2 = Section::factory()->create(['page_id' => $this->page->id, 'sort_order' => 1]);
        $section3 = Section::factory()->create(['page_id' => $this->page->id, 'sort_order' => 2]);

        $reorderData = [
            'section_ids' => [$section3->id, $section1->id, $section2->id],
        ];

        $response = $this->postJson("/api/v1/cms/pages/{$this->page->id}/sections/reorder", $reorderData);

        $response->assertOk();

        // Verify new order
        $section1->refresh();
        $section2->refresh();
        $section3->refresh();

        expect($section3->sort_order)->toBe(0);
        expect($section1->sort_order)->toBe(1);
        expect($section2->sort_order)->toBe(2);
    });

    it('can get available section types', function () {
        $response = $this->getJson('/api/v1/cms/section-types');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'Content' => [
                        '*' => [
                            'value',
                            'label',
                            'category',
                            'icon',
                            'default_content',
                        ]
                    ]
                ]
            ]);
    });

    it('can delete a section', function () {
        $section = Section::factory()->create(['page_id' => $this->page->id]);

        $response = $this->deleteJson("/api/v1/cms/sections/{$section->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('cms_sections', ['id' => $section->id]);
    });
});