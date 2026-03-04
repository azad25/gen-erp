<?php

namespace App\Domain\CMS\DTOs;

use App\Domain\CMS\Enums\SectionType;

readonly class CreateSectionData
{
    public function __construct(
        public int $pageId,
        public SectionType $type,
        public array $content = [],
        public int $sortOrder = 0,
        public bool $isVisible = true,
    ) {}

    public function toArray(): array
    {
        return [
            'page_id' => $this->pageId,
            'type' => $this->type,
            'content' => $this->content ?: $this->type->getDefaultContent(),
            'sort_order' => $this->sortOrder,
            'is_visible' => $this->isVisible,
        ];
    }
}