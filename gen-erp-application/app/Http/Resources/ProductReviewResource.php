<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product review API resource.
 */
class ProductReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'rating' => $this->rating,
            'title' => $this->title,
            'review' => $this->review,
            'customer' => [
                'id' => $this->customer_id,
                'name' => $this->customer_name,
                'email' => $this->when($this->shouldShowEmail($request), $this->customer_email),
            ],
            'is_verified_purchase' => $this->is_verified_purchase,
            'is_approved' => $this->is_approved,
            'helpful_count' => $this->helpful_count,
            'star_rating' => $this->getStarRating(),
            'rating_percentage' => $this->getRatingPercentage(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Determine if email should be shown based on request context.
     */
    private function shouldShowEmail(Request $request): bool
    {
        // Only show email in admin context or if it's the customer's own review
        return $request->routeIs('api.v1.*') || 
               ($request->user() && $request->user()->id === $this->customer_id);
    }
}