<?php

namespace App\Domain\CMS\Services;

use App\Domain\CMS\Contracts\ReviewServiceInterface;
use App\Domain\CMS\Models\ProductReview;
use App\Domain\CMS\Models\CustomerAccount;
use App\Domain\CMS\Models\PublicOrder;
use App\Domain\CMS\DTOs\CreateReviewData;
use App\Domain\CMS\Events\ReviewSubmitted;
use App\Domain\CMS\Events\ReviewApproved;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for managing product reviews.
 */
class ReviewService implements ReviewServiceInterface
{
    /**
     * Submit a new product review.
     */
    public function submitReview(int $siteId, CreateReviewData $data): ProductReview
    {
        // Check if customer already reviewed this product
        if ($data->customerId) {
            $existingReview = ProductReview::where('site_id', $siteId)
                ->where('product_id', $data->productId)
                ->where('customer_id', $data->customerId)
                ->first();

            if ($existingReview) {
                throw new \InvalidArgumentException('Customer has already reviewed this product.');
            }
        }

        // Validate rating
        if ($data->rating < 1 || $data->rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5.');
        }

        // Check if this is a verified purchase
        $isVerifiedPurchase = false;
        $orderId = null;

        if ($data->customerId && $data->orderId) {
            $order = PublicOrder::where('id', $data->orderId)
                ->where('customer_id', $data->customerId)
                ->where('site_id', $siteId)
                ->whereHas('items', function ($query) use ($data) {
                    $query->where('product_id', $data->productId);
                })
                ->first();

            if ($order) {
                $isVerifiedPurchase = true;
                $orderId = $order->id;
            }
        }

        $review = ProductReview::create([
            'site_id' => $siteId,
            'product_id' => $data->productId,
            'customer_id' => $data->customerId,
            'order_id' => $orderId,
            'rating' => $data->rating,
            'title' => $data->title,
            'review' => $data->review,
            'customer_name' => $data->customerName,
            'customer_email' => $data->customerEmail,
            'is_verified_purchase' => $isVerifiedPurchase,
            'is_approved' => false, // Reviews need approval by default
        ]);

        event(new ReviewSubmitted($review));

        return $review;
    }

    /**
     * Get reviews for a product.
     */
    public function getProductReviews(int $siteId, int $productId, bool $approvedOnly = true): Collection
    {
        $query = ProductReview::where('site_id', $siteId)
            ->where('product_id', $productId)
            ->with(['customer', 'order']);

        if ($approvedOnly) {
            $query->approved();
        }

        return $query->orderByNewest()->get();
    }

    /**
     * Get review statistics for a product.
     */
    public function getProductReviewStats(int $siteId, int $productId): array
    {
        $reviews = ProductReview::where('site_id', $siteId)
            ->where('product_id', $productId)
            ->approved()
            ->get();

        if ($reviews->isEmpty()) {
            return [
                'total_reviews' => 0,
                'average_rating' => 0,
                'rating_distribution' => [
                    1 => ['count' => 0, 'percentage' => 0],
                    2 => ['count' => 0, 'percentage' => 0],
                    3 => ['count' => 0, 'percentage' => 0],
                    4 => ['count' => 0, 'percentage' => 0],
                    5 => ['count' => 0, 'percentage' => 0],
                ],
                'verified_purchases' => 0,
            ];
        }

        $totalReviews = $reviews->count();
        $averageRating = round($reviews->avg('rating'), 1);
        $verifiedPurchases = $reviews->where('is_verified_purchase', true)->count();

        $ratingDistribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $count = $reviews->where('rating', $i)->count();
            $ratingDistribution[$i] = [
                'count' => $count,
                'percentage' => $totalReviews > 0 ? round(($count / $totalReviews) * 100, 1) : 0,
            ];
        }

        return [
            'total_reviews' => $totalReviews,
            'average_rating' => $averageRating,
            'rating_distribution' => $ratingDistribution,
            'verified_purchases' => $verifiedPurchases,
        ];
    }

    /**
     * Get reviews by customer.
     */
    public function getCustomerReviews(int $customerId): Collection
    {
        return ProductReview::where('customer_id', $customerId)
            ->with(['order'])
            ->orderByNewest()
            ->get();
    }

    /**
     * Approve a review.
     */
    public function approveReview(int $reviewId): ProductReview
    {
        $review = ProductReview::findOrFail($reviewId);
        $review->approve();

        event(new ReviewApproved($review));

        return $review->fresh();
    }

    /**
     * Reject a review.
     */
    public function rejectReview(int $reviewId): ProductReview
    {
        $review = ProductReview::findOrFail($reviewId);
        $review->reject();

        return $review->fresh();
    }

    /**
     * Mark review as helpful.
     */
    public function markReviewHelpful(int $reviewId): ProductReview
    {
        $review = ProductReview::findOrFail($reviewId);
        $review->markHelpful();

        return $review->fresh();
    }

    /**
     * Get pending reviews for moderation.
     */
    public function getPendingReviews(int $siteId): Collection
    {
        return ProductReview::where('site_id', $siteId)
            ->pending()
            ->with(['customer', 'order'])
            ->orderByNewest()
            ->get();
    }

    /**
     * Delete a review.
     */
    public function deleteReview(int $reviewId): bool
    {
        $review = ProductReview::findOrFail($reviewId);
        return $review->delete();
    }

    /**
     * Check if customer can review product.
     */
    public function canCustomerReviewProduct(int $customerId, int $productId): bool
    {
        // Check if customer has already reviewed this product
        $existingReview = ProductReview::where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->exists();

        if ($existingReview) {
            return false;
        }

        // Check if customer has purchased this product
        $hasPurchased = PublicOrder::where('customer_id', $customerId)
            ->whereHas('items', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->exists();

        return $hasPurchased;
    }

    /**
     * Get review summary for site.
     */
    public function getSiteReviewSummary(int $siteId): array
    {
        $totalReviews = ProductReview::where('site_id', $siteId)->count();
        $approvedReviews = ProductReview::where('site_id', $siteId)->approved()->count();
        $pendingReviews = ProductReview::where('site_id', $siteId)->pending()->count();
        $verifiedPurchases = ProductReview::where('site_id', $siteId)->verifiedPurchase()->count();

        $averageRating = ProductReview::where('site_id', $siteId)
            ->approved()
            ->avg('rating');

        $recentReviews = ProductReview::where('site_id', $siteId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            'total_reviews' => $totalReviews,
            'approved_reviews' => $approvedReviews,
            'pending_reviews' => $pendingReviews,
            'verified_purchases' => $verifiedPurchases,
            'average_rating' => $averageRating ? round($averageRating, 1) : 0,
            'recent_reviews' => $recentReviews,
        ];
    }
}