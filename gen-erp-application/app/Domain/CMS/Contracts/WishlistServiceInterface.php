<?php

namespace App\Domain\CMS\Contracts;

use App\Domain\CMS\Models\Wishlist;
use App\Domain\CMS\DTOs\AddToWishlistData;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface for wishlist service.
 */
interface WishlistServiceInterface
{
    /**
     * Add item to wishlist.
     */
    public function addToWishlist(AddToWishlistData $data): Wishlist;

    /**
     * Remove item from wishlist.
     */
    public function removeFromWishlist(int $customerId, int $productId, ?int $productVariantId = null): bool;

    /**
     * Get customer's wishlist.
     */
    public function getCustomerWishlist(int $customerId): Collection;

    /**
     * Check if item is in wishlist.
     */
    public function isInWishlist(int $customerId, int $productId, ?int $productVariantId = null): bool;

    /**
     * Clear customer's wishlist.
     */
    public function clearWishlist(int $customerId): bool;

    /**
     * Get wishlist item count for customer.
     */
    public function getWishlistCount(int $customerId): int;

    /**
     * Move wishlist item to cart.
     */
    public function moveToCart(int $wishlistItemId, int $quantity = 1): bool;

    /**
     * Get wishlist statistics for site.
     */
    public function getWishlistStatistics(int $siteId): array;
}