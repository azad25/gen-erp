<?php

use App\Domain\CMS\Services\WishlistService;
use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\CustomerAccount;
use App\Domain\CMS\Models\Wishlist;
use App\Domain\CMS\DTOs\AddToWishlistData;
use App\Domain\CMS\Events\ItemAddedToWishlist;
use App\Domain\Auth\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->wishlistService = app(WishlistService::class);
    $this->company = Company::factory()->create();
    $this->site = Site::factory()->create(['company_id' => $this->company->id]);
    $this->customer = CustomerAccount::factory()->create(['site_id' => $this->site->id]);
});

describe('Wishlist Management', function () {
    it('can add item to wishlist', function () {
        Event::fake();

        $data = new AddToWishlistData(
            customerId: $this->customer->id,
            productId: 1,
        );

        $wishlistItem = $this->wishlistService->addToWishlist($data);

        expect($wishlistItem)->toBeInstanceOf(Wishlist::class);
        expect($wishlistItem->customer_id)->toBe($this->customer->id);
        expect($wishlistItem->product_id)->toBe(1);
        expect($wishlistItem->product_variant_id)->toBeNull();

        Event::assertDispatched(ItemAddedToWishlist::class);
    });

    it('can add item with variant to wishlist', function () {
        $data = new AddToWishlistData(
            customerId: $this->customer->id,
            productId: 1,
            productVariantId: 5,
        );

        $wishlistItem = $this->wishlistService->addToWishlist($data);

        expect($wishlistItem->product_id)->toBe(1);
        expect($wishlistItem->product_variant_id)->toBe(5);
        expect($wishlistItem->hasVariant())->toBeTrue();
    });

    it('prevents duplicate wishlist items', function () {
        // Add item first time
        $data = new AddToWishlistData(
            customerId: $this->customer->id,
            productId: 1,
        );

        $this->wishlistService->addToWishlist($data);

        // Try to add same item again
        expect(fn() => $this->wishlistService->addToWishlist($data))
            ->toThrow(\InvalidArgumentException::class, 'Item is already in wishlist.');
    });

    it('allows same product with different variants', function () {
        // Add product without variant
        $data1 = new AddToWishlistData(
            customerId: $this->customer->id,
            productId: 1,
        );

        $wishlistItem1 = $this->wishlistService->addToWishlist($data1);

        // Add same product with variant
        $data2 = new AddToWishlistData(
            customerId: $this->customer->id,
            productId: 1,
            productVariantId: 5,
        );

        $wishlistItem2 = $this->wishlistService->addToWishlist($data2);

        expect($wishlistItem1->id)->not->toBe($wishlistItem2->id);
        expect($wishlistItem2->product_variant_id)->toBe(5);
    });

    it('can remove item from wishlist', function () {
        Wishlist::factory()->create([
            'customer_id' => $this->customer->id,
            'product_id' => 1,
            'product_variant_id' => null,
        ]);

        $removed = $this->wishlistService->removeFromWishlist($this->customer->id, 1);

        expect($removed)->toBeTrue();
        expect(Wishlist::where('customer_id', $this->customer->id)->where('product_id', 1)->exists())->toBeFalse();
    });

    it('can remove item with variant from wishlist', function () {
        Wishlist::factory()->create([
            'customer_id' => $this->customer->id,
            'product_id' => 1,
            'product_variant_id' => 5,
        ]);

        $removed = $this->wishlistService->removeFromWishlist($this->customer->id, 1, 5);

        expect($removed)->toBeTrue();
    });

    it('returns false when removing non-existent item', function () {
        $removed = $this->wishlistService->removeFromWishlist($this->customer->id, 999);

        expect($removed)->toBeFalse();
    });

    it('can clear entire wishlist', function () {
        Wishlist::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
        ]);

        $cleared = $this->wishlistService->clearWishlist($this->customer->id);

        expect($cleared)->toBeTrue();
        expect(Wishlist::where('customer_id', $this->customer->id)->count())->toBe(0);
    });

    it('returns false when clearing empty wishlist', function () {
        $cleared = $this->wishlistService->clearWishlist($this->customer->id);

        expect($cleared)->toBeFalse();
    });
});

describe('Wishlist Queries', function () {
    it('can get customer wishlist', function () {
        Wishlist::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
        ]);

        Wishlist::factory()->count(2)->create(); // Other customers

        $wishlist = $this->wishlistService->getCustomerWishlist($this->customer->id);

        expect($wishlist)->toHaveCount(3);
        expect($wishlist->every(fn($item) => $item->customer_id === $this->customer->id))->toBeTrue();
    });

    it('can check if item is in wishlist', function () {
        Wishlist::factory()->create([
            'customer_id' => $this->customer->id,
            'product_id' => 1,
            'product_variant_id' => null,
        ]);

        $isInWishlist = $this->wishlistService->isInWishlist($this->customer->id, 1);
        $isNotInWishlist = $this->wishlistService->isInWishlist($this->customer->id, 2);

        expect($isInWishlist)->toBeTrue();
        expect($isNotInWishlist)->toBeFalse();
    });

    it('can check if item with variant is in wishlist', function () {
        Wishlist::factory()->create([
            'customer_id' => $this->customer->id,
            'product_id' => 1,
            'product_variant_id' => 5,
        ]);

        $isInWishlist = $this->wishlistService->isInWishlist($this->customer->id, 1, 5);
        $isNotInWishlist = $this->wishlistService->isInWishlist($this->customer->id, 1, 6);

        expect($isInWishlist)->toBeTrue();
        expect($isNotInWishlist)->toBeFalse();
    });

    it('can get wishlist count', function () {
        Wishlist::factory()->count(5)->create([
            'customer_id' => $this->customer->id,
        ]);

        $count = $this->wishlistService->getWishlistCount($this->customer->id);

        expect($count)->toBe(5);
    });

    it('returns zero count for empty wishlist', function () {
        $count = $this->wishlistService->getWishlistCount($this->customer->id);

        expect($count)->toBe(0);
    });
});

describe('Wishlist Operations', function () {
    it('can move wishlist item to cart', function () {
        $wishlistItem = Wishlist::factory()->create([
            'customer_id' => $this->customer->id,
            'product_id' => 1,
        ]);

        $moved = $this->wishlistService->moveToCart($wishlistItem->id, 2);

        expect($moved)->toBeTrue();
        expect(Wishlist::find($wishlistItem->id))->toBeNull(); // Item removed from wishlist
    });

    it('throws exception for non-existent wishlist item', function () {
        expect(fn() => $this->wishlistService->moveToCart(999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });
});

describe('Wishlist Statistics', function () {
    it('can get wishlist statistics for site', function () {
        $customer1 = CustomerAccount::factory()->create(['site_id' => $this->site->id]);
        $customer2 = CustomerAccount::factory()->create(['site_id' => $this->site->id]);
        $otherSiteCustomer = CustomerAccount::factory()->create(); // Different site

        // Create wishlist items for site customers
        Wishlist::factory()->count(3)->create(['customer_id' => $customer1->id]);
        Wishlist::factory()->count(2)->create(['customer_id' => $customer2->id]);
        
        // Create wishlist items for other site customer (should not be counted)
        Wishlist::factory()->count(2)->create(['customer_id' => $otherSiteCustomer->id]);

        $stats = $this->wishlistService->getWishlistStatistics($this->site->id);

        expect($stats['total_wishlist_items'])->toBe(5);
        expect($stats['customers_with_wishlists'])->toBe(2);
        expect($stats['average_items_per_customer'])->toBe(2.5);
    });

    it('returns zero stats for site with no wishlists', function () {
        $stats = $this->wishlistService->getWishlistStatistics($this->site->id);

        expect($stats['total_wishlist_items'])->toBe(0);
        expect($stats['customers_with_wishlists'])->toBe(0);
        expect($stats['average_items_per_customer'])->toBe(0);
    });

    it('includes recent wishlist items in stats', function () {
        // Create recent items (within 30 days)
        Wishlist::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
            'created_at' => now()->subDays(15),
        ]);

        // Create old items (older than 30 days)
        Wishlist::factory()->count(2)->create([
            'customer_id' => $this->customer->id,
            'created_at' => now()->subDays(45),
        ]);

        $stats = $this->wishlistService->getWishlistStatistics($this->site->id);

        expect($stats['recent_wishlist_items'])->toBe(3);
    });

    it('includes most wishlisted products', function () {
        // Product 1: 3 wishlist items
        Wishlist::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
            'product_id' => 1,
        ]);

        // Product 2: 2 wishlist items
        $customer2 = CustomerAccount::factory()->create(['site_id' => $this->site->id]);
        Wishlist::factory()->count(2)->create([
            'customer_id' => $customer2->id,
            'product_id' => 2,
        ]);

        $stats = $this->wishlistService->getWishlistStatistics($this->site->id);

        expect($stats['most_wishlisted_products'])->toHaveCount(2);
        expect($stats['most_wishlisted_products'][0]['product_id'])->toBe(1);
        expect($stats['most_wishlisted_products'][0]['wishlist_count'])->toBe(3);
    });
});