<?php

namespace App\Domain\Inventory\DTOs;

/**
 * Data Transfer Object for updating a warehouse.
 */
readonly class UpdateWarehouseData
{
    public function __construct(
        public ?string $name = null,
        public ?string $code = null,
        public ?string $address = null,
        public ?bool $is_active = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'is_active' => $this->is_active,
        ], fn($value) => $value !== null);
    }
}