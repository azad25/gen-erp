<?php

namespace App\Domain\CMS\DTOs;

/**
 * Data Transfer Object for adding an item to wishlist.
 */
readonly class AddToWishlistData
{
    public function __construct(
        public int $customerId,
        public int $productId,
        public ?int $productVariantId = null,
    ) {}
}