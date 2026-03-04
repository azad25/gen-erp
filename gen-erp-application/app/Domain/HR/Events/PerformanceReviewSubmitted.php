<?php

namespace App\Domain\HR\Events;

use App\Domain\HR\Models\PerformanceReview;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a performance review is submitted
 */
class PerformanceReviewSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly PerformanceReview $review
    ) {}
}