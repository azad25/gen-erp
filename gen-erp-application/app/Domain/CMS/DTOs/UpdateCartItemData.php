<?php

namespace App\Domain\CMS\DTOs;

/**
 * Data Transfer Object for updating cart items.
 */
readonly class UpdateCartItemData
{
    public function __construct(
        public int $quantity,
    ) {}

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'quantity' => $this->quantity,
        ];
    }
}