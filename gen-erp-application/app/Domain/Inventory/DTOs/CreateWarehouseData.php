<?php

namespace App\Domain\Inventory\DTOs;

/**
 * Data Transfer Object for creating a new warehouse.
 */
readonly class CreateWarehouseData
{
    public function __construct(
        public int $company_id,
        public string $name,
        public string $code,
        public ?string $address = null,
        public bool $is_active = true,
    ) {}

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'is_active' => $this->is_active,
        ];
    }
}