<?php

namespace App\Domain\HR\DTOs;

/**
 * Data Transfer Object for updating a department.
 */
readonly class UpdateDepartmentData
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $code = null,
        public ?int $parent_id = null,
        public ?int $manager_id = null,
        public ?bool $is_active = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'parent_id' => $this->parent_id,
            'manager_id' => $this->manager_id,
            'is_active' => $this->is_active,
        ], fn($value) => $value !== null);
    }
}