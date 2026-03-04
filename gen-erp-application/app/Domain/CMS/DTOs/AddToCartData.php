<?php

namespace App\Domain\CMS\DTOs;

/**
 * Data Transfer Object for adding items to cart.
 */
readonly class AddToCartData
{
    public function __construct(
        public int $productId,
        public ?int $productVariantId,
        public int $quantity,
        public float $price,
    ) {}

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'product_variant_id' => $this->productVariantId,
            'quantity' => $this->quantity,
            'price' => $this->price,
        ];
    }
}