<?php

namespace App\Domain\CMS\Services;

use App\Domain\CMS\Contracts\CMSServiceInterface;
use App\Domain\CMS\Contracts\PageBuilderServiceInterface;
use App\Domain\CMS\Models\Page;
use App\Domain\CMS\Models\Section;
use App\Domain\CMS\Enums\SectionType;
use App\Domain\CMS\DTOs\CreateSectionData;
use App\Domain\CMS\DTOs\UpdateSectionData;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for page builder operations.
 */
class PageBuilderService implements PageBuilderServiceInterface
{
    public function __construct(
        private readonly CMSServiceInterface $cmsService
    ) {}

    /**
     * Get all available section types with their metadata.
     */
    public function getAvailableSectionTypes(): array
    {
        $sectionTypes = [];
        
        foreach (SectionType::cases() as $type) {
            $sectionTypes[] = [
                'value' => $type->value,
                'label' => $type->label(),
                'category' => $type->category(),
                'icon' => $type->icon(),
                'default_content' => $type->getDefaultContent(),
            ];
        }

        // Group by category
        $grouped = [];
        foreach ($sectionTypes as $type) {
            $category = $type['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $type;
        }

        return $grouped;
    }

    /**
     * Add a new section to a page.
     */
    public function addSectionToPage(int $pageId, SectionType $sectionType, int $order = null): Section
    {
        $page = Page::findOrFail($pageId);
        
        // If no order specified, add to the end
        if ($order === null) {
            $order = $page->sections()->max('order') + 1;
        }

        // Shift existing sections down if inserting in the middle
        if ($order <= $page->sections()->max('order')) {
            $page->sections()
                ->where('order', '>=', $order)
                ->increment('order');
        }

        $sectionData = new CreateSectionData(
            pageId: $pageId,
            type: $sectionType,
            content: $sectionType->getDefaultContent(),
            order: $order,
            isVisible: true
        );

        return $this->cmsService->createSection($sectionData);
    }

    /**
     * Reorder sections on a page.
     */
    public function reorderSections(int $pageId, array $sectionIds): bool
    {
        $page = Page::findOrFail($pageId);
        
        foreach ($sectionIds as $index => $sectionId) {
            $page->sections()
                ->where('id', $sectionId)
                ->update(['order' => $index + 1]);
        }

        return true;
    }

    /**
     * Duplicate a section.
     */
    public function duplicateSection(int $sectionId): Section
    {
        $originalSection = Section::findOrFail($sectionId);
        
        // Find the next order position
        $nextOrder = $originalSection->page->sections()
            ->where('order', '>', $originalSection->order)
            ->min('order') ?? ($originalSection->order + 1);

        // Shift sections down to make room
        $originalSection->page->sections()
            ->where('order', '>=', $nextOrder)
            ->increment('order');

        $sectionData = new CreateSectionData(
            pageId: $originalSection->page_id,
            type: SectionType::from($originalSection->type),
            content: $originalSection->content,
            order: $nextOrder,
            isVisible: $originalSection->is_visible
        );

        return $this->cmsService->createSection($sectionData);
    }

    /**
     * Update section content.
     */
    public function updateSectionContent(int $sectionId, array $content): Section
    {
        $section = Section::findOrFail($sectionId);
        
        $updateData = new UpdateSectionData(
            type: SectionType::from($section->type),
            content: $content,
            isVisible: $section->is_visible
        );

        return $this->cmsService->updateSection($sectionId, $updateData);
    }

    /**
     * Toggle section visibility.
     */
    public function toggleSectionVisibility(int $sectionId): Section
    {
        $section = Section::findOrFail($sectionId);
        
        $updateData = new UpdateSectionData(
            type: SectionType::from($section->type),
            content: $section->content,
            isVisible: !$section->is_visible
        );

        return $this->cmsService->updateSection($sectionId, $updateData);
    }

    /**
     * Get page with sections for builder.
     */
    public function getPageForBuilder(int $pageId): array
    {
        $page = Page::with(['sections' => function ($query) {
            $query->orderBy('order');
        }])->findOrFail($pageId);

        return [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
                'is_published' => $page->is_published,
                'is_homepage' => $page->is_homepage,
            ],
            'sections' => $page->sections->map(function ($section) {
                return [
                    'id' => $section->id,
                    'type' => $section->type,
                    'content' => $section->content,
                    'order' => $section->order,
                    'is_visible' => $section->is_visible,
                    'created_at' => $section->created_at,
                    'updated_at' => $section->updated_at,
                ];
            })->toArray(),
        ];
    }

    /**
     * Preview page as it would appear to visitors.
     */
    public function previewPage(int $pageId): array
    {
        $page = Page::with(['sections' => function ($query) {
            $query->where('is_visible', true)->orderBy('order');
        }])->findOrFail($pageId);

        return [
            'page' => [
                'title' => $page->title,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
            ],
            'sections' => $page->sections->map(function ($section) {
                return [
                    'type' => $section->type,
                    'content' => $section->content,
                ];
            })->toArray(),
        ];
    }
}