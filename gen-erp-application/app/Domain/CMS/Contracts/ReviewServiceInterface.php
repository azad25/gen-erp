<?php

namespace App\Domain\CMS\Contracts;

use App\Domain\CMS\Models\ProductReview;
use App\Domain\CMS\DTOs\CreateReviewData;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface for product review service.
 */
interface ReviewServiceInterface
{
    /**
     * Submit a new product review.
     */
    public function submitReview(int $siteId, CreateReviewData $data): ProductReview;

    /**
     * Get reviews for a product.
     */
    public function getProductReviews(int $siteId, int $productId, bool $approvedOnly = true): Collection;

    /**
     * Get review statistics for a product.
     */
    public function getProductReviewStats(int $siteId, int $productId): array;

    /**
     * Get reviews by customer.
     */
    public function getCustomerReviews(int $customerId): Collection;

    /**
     * Approve a review.
     */
    public function approveReview(int $reviewId): ProductReview;

    /**
     * Reject a review.
     */
    public function rejectReview(int $reviewId): ProductReview;

    /**
     * Mark review as helpful.
     */
    public function markReviewHelpful(int $reviewId): ProductReview;

    /**
     * Get pending reviews for moderation.
     */
    public function getPendingReviews(int $siteId): Collection;

    /**
     * Delete a review.
     */
    public function deleteReview(int $reviewId): bool;

    /**
     * Check if customer can review product.
     */
    public function canCustomerReviewProduct(int $customerId, int $productId): bool;

    /**
     * Get review summary for site.
     */
    public function getSiteReviewSummary(int $siteId): array;
}