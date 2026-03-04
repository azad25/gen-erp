<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\CMS\Services\ReviewService;
use App\Domain\CMS\Models\Site;
use App\Http\Resources\ProductReviewResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="CMS Reviews",
 *     description="CMS product review management endpoints"
 * )
 */
class ReviewController extends BaseApiController
{
    public function __construct(
        private readonly ReviewService $reviewService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/cms/reviews",
     *     summary="List all reviews for company sites",
     *     tags={"CMS Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="site_id",
     *         in="query",
     *         description="Filter by site ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by approval status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"approved", "pending"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reviews retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ProductReview"))
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Site::class);

        $validated = $request->validate([
            'site_id' => 'nullable|integer|exists:cms_sites,id',
            'status' => 'nullable|string|in:approved,pending',
        ]);

        $companyId = $this->getCurrentCompany()->id;
        
        // Get all sites for the company
        $sites = Site::where('company_id', $companyId)->pluck('id');

        if (empty($sites->toArray())) {
            return $this->success([]);
        }

        // Filter by specific site if provided
        if ($validated['site_id'] ?? null) {
            $site = Site::where('id', $validated['site_id'])
                ->where('company_id', $companyId)
                ->firstOrFail();
            $sites = collect([$site->id]);
        }

        $reviews = collect();
        foreach ($sites as $siteId) {
            $siteReviews = match ($validated['status'] ?? null) {
                'approved' => $this->reviewService->getProductReviews($siteId, null, true),
                'pending' => $this->reviewService->getPendingReviews($siteId),
                default => $this->reviewService->getProductReviews($siteId, null, false),
            };
            $reviews = $reviews->merge($siteReviews);
        }

        return $this->success(ProductReviewResource::collection($reviews->sortByDesc('created_at')));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cms/reviews/{id}/approve",
     *     summary="Approve a review",
     *     tags={"CMS Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Review ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review approved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Review approved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/ProductReview")
     *         )
     *     )
     * )
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $this->authorize('update', Site::class);

        try {
            $review = $this->reviewService->approveReview($id);
            
            return $this->success(
                new ProductReviewResource($review),
                'Review approved successfully.'
            );
        } catch (\Exception $e) {
            return $this->error('Review not found.', 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cms/reviews/{id}/reject",
     *     summary="Reject a review",
     *     tags={"CMS Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Review ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review rejected successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Review rejected successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/ProductReview")
     *         )
     *     )
     * )
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $this->authorize('update', Site::class);

        try {
            $review = $this->reviewService->rejectReview($id);
            
            return $this->success(
                new ProductReviewResource($review),
                'Review rejected successfully.'
            );
        } catch (\Exception $e) {
            return $this->error('Review not found.', 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/cms/reviews/{id}",
     *     summary="Delete a review",
     *     tags={"CMS Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Review ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Review deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorize('delete', Site::class);

        try {
            $deleted = $this->reviewService->deleteReview($id);
            
            if (!$deleted) {
                return $this->error('Failed to delete review.', 422);
            }
            
            return $this->success(null, 'Review deleted successfully.');
        } catch (\Exception $e) {
            return $this->error('Review not found.', 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cms/reviews/statistics",
     *     summary="Get review statistics for company sites",
     *     tags={"CMS Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="site_id",
     *         in="query",
     *         description="Filter by site ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_reviews", type="integer", example=150),
     *                 @OA\Property(property="approved_reviews", type="integer", example=120),
     *                 @OA\Property(property="pending_reviews", type="integer", example=30),
     *                 @OA\Property(property="verified_purchases", type="integer", example=80),
     *                 @OA\Property(property="average_rating", type="number", format="float", example=4.2),
     *                 @OA\Property(property="recent_reviews", type="integer", example=25)
     *             )
     *         )
     *     )
     * )
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Site::class);

        $validated = $request->validate([
            'site_id' => 'nullable|integer|exists:cms_sites,id',
        ]);

        $companyId = $this->getCurrentCompany()->id;

        if ($validated['site_id'] ?? null) {
            $site = Site::where('id', $validated['site_id'])
                ->where('company_id', $companyId)
                ->firstOrFail();
            
            $stats = $this->reviewService->getSiteReviewSummary($site->id);
        } else {
            // Aggregate stats for all company sites
            $sites = Site::where('company_id', $companyId)->get();
            $stats = [
                'total_reviews' => 0,
                'approved_reviews' => 0,
                'pending_reviews' => 0,
                'verified_purchases' => 0,
                'average_rating' => 0,
                'recent_reviews' => 0,
            ];

            $totalRatings = 0;
            $ratingSum = 0;

            foreach ($sites as $site) {
                $siteStats = $this->reviewService->getSiteReviewSummary($site->id);
                $stats['total_reviews'] += $siteStats['total_reviews'];
                $stats['approved_reviews'] += $siteStats['approved_reviews'];
                $stats['pending_reviews'] += $siteStats['pending_reviews'];
                $stats['verified_purchases'] += $siteStats['verified_purchases'];
                $stats['recent_reviews'] += $siteStats['recent_reviews'];

                if ($siteStats['average_rating'] > 0) {
                    $ratingSum += $siteStats['average_rating'] * $siteStats['approved_reviews'];
                    $totalRatings += $siteStats['approved_reviews'];
                }
            }

            $stats['average_rating'] = $totalRatings > 0 ? round($ratingSum / $totalRatings, 1) : 0;
        }

        return $this->success($stats);
    }
}