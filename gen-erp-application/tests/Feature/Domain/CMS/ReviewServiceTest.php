<?php

use App\Domain\CMS\Services\ReviewService;
use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\CustomerAccount;
use App\Domain\CMS\Models\ProductReview;
use App\Domain\CMS\Models\PublicOrder;
use App\Domain\CMS\DTOs\CreateReviewData;
use App\Domain\CMS\Events\ReviewSubmitted;
use App\Domain\CMS\Events\ReviewApproved;
use App\Domain\Auth\Models\Company;
use App\Domain\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->reviewService = app(ReviewService::class);
    $this->company = Company::factory()->create();
    $this->site = Site::factory()->create(['company_id' => $this->company->id]);
    $this->customer = CustomerAccount::factory()->create(['site_id' => $this->site->id]);
    $this->product = Product::factory()->create(['company_id' => $this->company->id]);
});

describe('Review Submission', function () {
    it('can submit a new review', function () {
        Event::fake();

        $data = new CreateReviewData(
            productId: $this->product->id,
            rating: 5,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            title: 'Great product!',
            review: 'I love this product. Highly recommended.',
            customerId: $this->customer->id,
        );

        $review = $this->reviewService->submitReview($this->site->id, $data);

        expect($review)->toBeInstanceOf(ProductReview::class);
        expect($review->product_id)->toBe($this->product->id);
        expect($review->rating)->toBe(5);
        expect($review->title)->toBe('Great product!');
        expect($review->customer_name)->toBe('John Doe');
        expect($review->is_approved)->toBeFalse(); // Reviews need approval by default

        Event::assertDispatched(ReviewSubmitted::class);
    });

    it('validates rating range', function () {
        $data = new CreateReviewData(
            productId: $this->product->id,
            rating: 6, // Invalid rating
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
        );

        expect(fn() => $this->reviewService->submitReview($this->site->id, $data))
            ->toThrow(\InvalidArgumentException::class, 'Rating must be between 1 and 5.');
    });

    it('prevents duplicate reviews from same customer', function () {
        // Create existing review
        ProductReview::factory()->create([
            'site_id' => $this->site->id,
            'product_id' => $this->product->id,
            'customer_id' => $this->customer->id,
        ]);

        $data = new CreateReviewData(
            productId: $this->product->id,
            rating: 4,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            customerId: $this->customer->id,
        );

        expect(fn() => $this->reviewService->submitReview($this->site->id, $data))
            ->toThrow(\InvalidArgumentException::class, 'Customer has already reviewed this product.');
    });

    it('can submit guest review', function () {
        $data = new CreateReviewData(
            productId: $this->product->id,
            rating: 4,
            customerName: 'Guest User',
            customerEmail: 'guest@example.com',
        );

        $review = $this->reviewService->submitReview($this->site->id, $data);

        expect($review->customer_id)->toBeNull();
        expect($review->customer_name)->toBe('Guest User');
        expect($review->is_verified_purchase)->toBeFalse();
    });

    it('marks as verified purchase when order exists', function () {
        $order = PublicOrder::factory()->create([
            'site_id' => $this->site->id,
            'customer_id' => $this->customer->id,
        ]);

        // Create order item for the product
        \DB::table('cms_public_order_items')->insert([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => 'Test Product',
            'product_sku' => 'TEST-001',
            'quantity' => 1,
            'unit_price' => 100.00,
            'subtotal' => 100.00,
            'tax_amount' => 8.00,
            'total' => 108.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $data = new CreateReviewData(
            productId: $this->product->id,
            rating: 5,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            customerId: $this->customer->id,
            orderId: $order->id,
        );

        $review = $this->reviewService->submitReview($this->site->id, $data);

        expect($review->is_verified_purchase)->toBeTrue();
        expect($review->order_id)->toBe($order->id);
    });
});

describe('Review Management', function () {
    it('can approve a review', function () {
        Event::fake();

        $review = ProductReview::factory()->pending()->create([
            'site_id' => $this->site->id,
        ]);

        $approvedReview = $this->reviewService->approveReview($review->id);

        expect($approvedReview->is_approved)->toBeTrue();
        Event::assertDispatched(ReviewApproved::class);
    });

    it('can reject a review', function () {
        $review = ProductReview::factory()->approved()->create([
            'site_id' => $this->site->id,
        ]);

        $rejectedReview = $this->reviewService->rejectReview($review->id);

        expect($rejectedReview->is_approved)->toBeFalse();
    });

    it('can mark review as helpful', function () {
        $review = ProductReview::factory()->create([
            'site_id' => $this->site->id,
            'helpful_count' => 5,
        ]);

        $updatedReview = $this->reviewService->markReviewHelpful($review->id);

        expect($updatedReview->helpful_count)->toBe(6);
    });

    it('can delete a review', function () {
        $review = ProductReview::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $deleted = $this->reviewService->deleteReview($review->id);

        expect($deleted)->toBeTrue();
        expect(ProductReview::find($review->id))->toBeNull();
    });
});

describe('Review Queries', function () {
    it('can get product reviews', function () {
        ProductReview::factory()->count(3)->approved()->create([
            'site_id' => $this->site->id,
            'product_id' => $this->product->id,
        ]);

        ProductReview::factory()->count(2)->pending()->create([
            'site_id' => $this->site->id,
            'product_id' => $this->product->id,
        ]);

        $reviews = $this->reviewService->getProductReviews($this->site->id, $this->product->id, true);

        expect($reviews)->toHaveCount(3); // Only approved reviews
    });

    it('can get all product reviews including pending', function () {
        ProductReview::factory()->count(3)->approved()->create([
            'site_id' => $this->site->id,
            'product_id' => $this->product->id,
        ]);

        ProductReview::factory()->count(2)->pending()->create([
            'site_id' => $this->site->id,
            'product_id' => $this->product->id,
        ]);

        $reviews = $this->reviewService->getProductReviews($this->site->id, $this->product->id, false);

        expect($reviews)->toHaveCount(5); // All reviews
    });

    it('can get customer reviews', function () {
        ProductReview::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
        ]);

        ProductReview::factory()->count(2)->create(); // Other customers

        $reviews = $this->reviewService->getCustomerReviews($this->customer->id);

        expect($reviews)->toHaveCount(3);
    });

    it('can get pending reviews for moderation', function () {
        ProductReview::factory()->count(2)->pending()->create([
            'site_id' => $this->site->id,
        ]);

        ProductReview::factory()->count(3)->approved()->create([
            'site_id' => $this->site->id,
        ]);

        $pendingReviews = $this->reviewService->getPendingReviews($this->site->id);

        expect($pendingReviews)->toHaveCount(2);
        expect($pendingReviews->every(fn($review) => !$review->is_approved))->toBeTrue();
    });
});

describe('Review Statistics', function () {
    it('can get product review statistics', function () {
        // Ensure no existing reviews for this product
        ProductReview::where('site_id', $this->site->id)
            ->where('product_id', $this->product->id)
            ->delete();

        // Create reviews with specific ratings - manually calculate: (3×5 + 2×4 + 1×3) / 6 = 26/6 = 4.33
        // But we want 4.5, so let's create: (3×5 + 3×4) / 6 = (15 + 12) / 6 = 27/6 = 4.5
        ProductReview::factory()->count(3)->create([
            'site_id' => $this->site->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'is_approved' => true,
        ]);

        ProductReview::factory()->count(3)->create([
            'site_id' => $this->site->id,
            'product_id' => $this->product->id,
            'rating' => 4,
            'is_approved' => true,
        ]);

        $stats = $this->reviewService->getProductReviewStats($this->site->id, $this->product->id);

        expect($stats['total_reviews'])->toBe(6);
        expect($stats['average_rating'])->toBe(4.5);
        expect($stats['rating_distribution'][5]['count'])->toBe(3);
        expect($stats['rating_distribution'][4]['count'])->toBe(3);
        expect($stats['rating_distribution'][3]['count'])->toBe(0);
        expect($stats['rating_distribution'][2]['count'])->toBe(0);
        expect($stats['rating_distribution'][1]['count'])->toBe(0);
    });

    it('returns empty stats for product with no reviews', function () {
        $stats = $this->reviewService->getProductReviewStats($this->site->id, 999);

        expect($stats['total_reviews'])->toBe(0);
        expect($stats['average_rating'])->toBe(0);
        expect($stats['rating_distribution'][1]['count'])->toBe(0);
    });

    it('can get site review summary', function () {
        // Create approved reviews
        ProductReview::factory()->count(10)->create([
            'site_id' => $this->site->id,
            'is_approved' => true,
        ]);

        // Create pending reviews
        ProductReview::factory()->count(3)->create([
            'site_id' => $this->site->id,
            'is_approved' => false,
        ]);

        // Create verified purchase reviews
        ProductReview::factory()->count(2)->create([
            'site_id' => $this->site->id,
            'is_verified_purchase' => true,
        ]);

        $summary = $this->reviewService->getSiteReviewSummary($this->site->id);

        expect($summary['total_reviews'])->toBe(15);
        expect($summary['approved_reviews'])->toBe(12); // 10 + 2 verified
        expect($summary['pending_reviews'])->toBe(3);
        expect($summary['verified_purchases'])->toBeGreaterThanOrEqual(2);
    });
});

describe('Review Permissions', function () {
    it('can check if customer can review product', function () {
        // Customer has purchased the product
        $order = PublicOrder::factory()->create([
            'site_id' => $this->site->id,
            'customer_id' => $this->customer->id,
        ]);

        \DB::table('cms_public_order_items')->insert([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => 'Test Product',
            'product_sku' => 'TEST-001',
            'quantity' => 1,
            'unit_price' => 100.00,
            'subtotal' => 100.00,
            'tax_amount' => 8.00,
            'total' => 108.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $canReview = $this->reviewService->canCustomerReviewProduct($this->customer->id, $this->product->id);

        expect($canReview)->toBeTrue();
    });

    it('prevents review if customer has not purchased product', function () {
        $canReview = $this->reviewService->canCustomerReviewProduct($this->customer->id, $this->product->id);

        expect($canReview)->toBeFalse();
    });

    it('prevents review if customer already reviewed product', function () {
        // Customer has purchased the product
        $order = PublicOrder::factory()->create([
            'site_id' => $this->site->id,
            'customer_id' => $this->customer->id,
        ]);

        \DB::table('cms_public_order_items')->insert([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => 'Test Product',
            'product_sku' => 'TEST-001',
            'quantity' => 1,
            'unit_price' => 100.00,
            'subtotal' => 100.00,
            'tax_amount' => 8.00,
            'total' => 108.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Customer already reviewed
        ProductReview::factory()->create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
        ]);

        $canReview = $this->reviewService->canCustomerReviewProduct($this->customer->id, $this->product->id);

        expect($canReview)->toBeFalse();
    });
});