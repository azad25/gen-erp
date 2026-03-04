<?php

namespace App\Domain\CMS\DTOs;

use App\Domain\CMS\Enums\SectionType;

readonly class UpdateSectionData
{
    public function __construct(
        public ?SectionType $type = null,
        public ?array $content = null,
        public ?int $sortOrder = null,
        public ?bool $isVisible = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'content' => $this->content,
            'sort_order' => $this->sortOrder,
            'is_visible' => $this->isVisible,
        ], fn($value) => $value !== null);
    }
}