<?php

namespace App\Domain\CMS\DTOs;

/**
 * Data Transfer Object for creating a product review.
 */
readonly class CreateReviewData
{
    public function __construct(
        public int $productId,
        public int $rating,
        public string $customerName,
        public string $customerEmail,
        public ?string $title = null,
        public ?string $review = null,
        public ?int $customerId = null,
        public ?int $orderId = null,
        public bool $isVerifiedPurchase = false,
    ) {}
}