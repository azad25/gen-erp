<?php

namespace App\Domain\HR\DTOs;

/**
 * Data Transfer Object for creating a new department.
 */
readonly class CreateDepartmentData
{
    public function __construct(
        public int $company_id,
        public string $name,
        public ?string $description = null,
        public ?string $code = null,
        public ?int $parent_id = null,
        public ?int $manager_id = null,
        public bool $is_active = true,
    ) {}

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'parent_id' => $this->parent_id,
            'manager_id' => $this->manager_id,
            'is_active' => $this->is_active,
        ];
    }
}