<?php

namespace App\Domain\CMS\Events;

use App\Domain\CMS\Models\ProductReview;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a product review is submitted.
 */
class ReviewSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ProductReview $review
    ) {}
}