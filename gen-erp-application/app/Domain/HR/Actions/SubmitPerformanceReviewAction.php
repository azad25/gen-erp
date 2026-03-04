<?php

namespace App\Domain\HR\Actions;

use App\Domain\HR\DTOs\PerformanceReviewData;
use App\Domain\HR\Models\PerformanceReview;

/**
 * Action to create or update performance review
 */
class SubmitPerformanceReviewAction
{
    public function execute(PerformanceReviewData $data): PerformanceReview
    {
        $review = PerformanceReview::create($data->toArray());

        return $review;
    }

    public function update(PerformanceReview $review, PerformanceReviewData $data): PerformanceReview
    {
        $review->update($data->toArray());

        return $review->fresh();
    }

    public function submit(PerformanceReview $review): PerformanceReview
    {
        $review->submit();

        return $review->fresh();
    }

    public function acknowledge(PerformanceReview $review): PerformanceReview
    {
        $review->acknowledge();

        return $review->fresh();
    }
}