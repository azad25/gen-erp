<?php

namespace App\Domain\CMS\Contracts;

use App\Domain\CMS\Models\Section;
use App\Domain\CMS\Enums\SectionType;

interface PageBuilderServiceInterface
{
    /**
     * Get all available section types with their metadata.
     */
    public function getAvailableSectionTypes(): array;

    /**
     * Add a new section to a page.
     */
    public function addSectionToPage(int $pageId, SectionType $sectionType, int $order = null): Section;

    /**
     * Reorder sections on a page.
     */
    public function reorderSections(int $pageId, array $sectionIds): bool;

    /**
     * Duplicate a section.
     */
    public function duplicateSection(int $sectionId): Section;

    /**
     * Update section content.
     */
    public function updateSectionContent(int $sectionId, array $content): Section;

    /**
     * Toggle section visibility.
     */
    public function toggleSectionVisibility(int $sectionId): Section;

    /**
     * Get page with sections for builder.
     */
    public function getPageForBuilder(int $pageId): array;

    /**
     * Preview page as it would appear to visitors.
     */
    public function previewPage(int $pageId): array;
}