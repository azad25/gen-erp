<?php

namespace App\Http\Controllers\Api\Public;

use App\Domain\CMS\Services\ReviewService;
use App\Domain\CMS\Models\Site;
use App\Domain\CMS\DTOs\CreateReviewData;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\ProductReviewResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public API controller for product reviews.
 */
class ReviewController extends BaseApiController
{
    public function __construct(
        private readonly ReviewService $reviewService
    ) {}

    /**
     * Get reviews for a product.
     */
    public function index(Request $request, string $tenant, int $productId): JsonResponse
    {
        $site = Site::where('subdomain', $tenant)
            ->orWhere('domain', $tenant)
            ->published()
            ->firstOrFail();

        $reviews = $this->reviewService->getProductReviews($site->id, $productId, true);

        return $this->success(ProductReviewResource::collection($reviews));
    }

    /**
     * Get review statistics for a product.
     */
    public function stats(Request $request, string $tenant, int $productId): JsonResponse
    {
        $site = Site::where('subdomain', $tenant)
            ->orWhere('domain', $tenant)
            ->published()
            ->firstOrFail();

        $stats = $this->reviewService->getProductReviewStats($site->id, $productId);

        return $this->success($stats);
    }

    /**
     * Submit a new review.
     */
    public function store(Request $request, string $tenant, int $productId): JsonResponse
    {
        $site = Site::where('subdomain', $tenant)
            ->orWhere('domain', $tenant)
            ->published()
            ->firstOrFail();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'review' => 'nullable|string|max:2000',
            'customer_name' => 'required|string|max:100',
            'customer_email' => 'required|email|max:255',
            'order_id' => 'nullable|integer|exists:cms_public_orders,id',
        ]);

        try {
            $customerId = $this->getCustomerIdFromToken($request);

            $data = new CreateReviewData(
                productId: $productId,
                rating: $validated['rating'],
                customerName: $validated['customer_name'],
                customerEmail: $validated['customer_email'],
                title: $validated['title'] ?? null,
                review: $validated['review'] ?? null,
                customerId: $customerId,
                orderId: $validated['order_id'] ?? null,
            );

            $review = $this->reviewService->submitReview($site->id, $data);

            return $this->success(
                new ProductReviewResource($review),
                'Review submitted successfully. It will be published after approval.',
                201
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Mark a review as helpful.
     */
    public function markHelpful(Request $request, string $tenant, int $productId, int $reviewId): JsonResponse
    {
        $site = Site::where('subdomain', $tenant)
            ->orWhere('domain', $tenant)
            ->published()
            ->firstOrFail();

        try {
            $review = $this->reviewService->markReviewHelpful($reviewId);

            return $this->success(
                new ProductReviewResource($review),
                'Review marked as helpful.'
            );
        } catch (\Exception $e) {
            return $this->error('Review not found.', 404);
        }
    }

    /**
     * Get customer's reviews (requires authentication).
     */
    public function customerReviews(Request $request, string $tenant): JsonResponse
    {
        $customerId = $this->getCustomerIdFromToken($request);
        
        if (!$customerId) {
            return $this->error('Unauthorized.', 401);
        }

        $reviews = $this->reviewService->getCustomerReviews($customerId);

        return $this->success(ProductReviewResource::collection($reviews));
    }

    /**
     * Extract customer ID from simple token.
     * Note: This is a basic implementation. Use Laravel Sanctum for production.
     */
    private function getCustomerIdFromToken(Request $request): ?int
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return null;
        }

        try {
            $decoded = base64_decode($token);
            $parts = explode(':', $decoded);
            
            if (count($parts) >= 3) {
                return (int) $parts[0];
            }
        } catch (\Exception $e) {
            // Invalid token
        }

        return null;
    }
}