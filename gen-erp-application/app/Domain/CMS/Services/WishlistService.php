<?php

namespace App\Domain\CMS\Services;

use App\Domain\CMS\Contracts\WishlistServiceInterface;
use App\Domain\CMS\Models\Wishlist;
use App\Domain\CMS\Models\CustomerAccount;
use App\Domain\CMS\DTOs\AddToWishlistData;
use App\Domain\CMS\Events\ItemAddedToWishlist;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;

/**
 * Service for managing customer wishlists.
 */
class WishlistService implements WishlistServiceInterface
{
    /**
     * Add item to wishlist.
     */
    public function addToWishlist(AddToWishlistData $data): Wishlist
    {
        // Check if customer exists
        $customer = CustomerAccount::findOrFail($data->customerId);

        // Check if item already exists in wishlist
        $existingItem = Wishlist::where('customer_id', $data->customerId)
            ->where('product_id', $data->productId)
            ->where('product_variant_id', $data->productVariantId)
            ->first();

        if ($existingItem) {
            throw new \InvalidArgumentException('Item is already in wishlist.');
        }

        $wishlistItem = Wishlist::create([
            'customer_id' => $data->customerId,
            'product_id' => $data->productId,
            'product_variant_id' => $data->productVariantId,
        ]);

        event(new ItemAddedToWishlist($wishlistItem));

        return $wishlistItem;
    }

    /**
     * Remove item from wishlist.
     */
    public function removeFromWishlist(int $customerId, int $productId, ?int $productVariantId = null): bool
    {
        $query = Wishlist::where('customer_id', $customerId)
            ->where('product_id', $productId);

        if ($productVariantId) {
            $query->where('product_variant_id', $productVariantId);
        } else {
            $query->whereNull('product_variant_id');
        }

        $wishlistItem = $query->first();

        if (!$wishlistItem) {
            return false;
        }

        return $wishlistItem->delete();
    }

    /**
     * Get customer's wishlist.
     */
    public function getCustomerWishlist(int $customerId): Collection
    {
        return Wishlist::forCustomer($customerId)
            ->with(['customer'])
            ->orderByNewest()
            ->get();
    }

    /**
     * Check if item is in wishlist.
     */
    public function isInWishlist(int $customerId, int $productId, ?int $productVariantId = null): bool
    {
        $query = Wishlist::where('customer_id', $customerId)
            ->where('product_id', $productId);

        if ($productVariantId) {
            $query->where('product_variant_id', $productVariantId);
        } else {
            $query->whereNull('product_variant_id');
        }

        return $query->exists();
    }

    /**
     * Clear customer's wishlist.
     */
    public function clearWishlist(int $customerId): bool
    {
        $deletedCount = Wishlist::forCustomer($customerId)->delete();
        return $deletedCount > 0;
    }

    /**
     * Get wishlist item count for customer.
     */
    public function getWishlistCount(int $customerId): int
    {
        return Wishlist::forCustomer($customerId)->count();
    }

    /**
     * Move wishlist item to cart.
     */
    public function moveToCart(int $wishlistItemId, int $quantity = 1): bool
    {
        $wishlistItem = Wishlist::findOrFail($wishlistItemId);

        // This would integrate with CartService when products are available
        // For now, we'll just remove from wishlist
        // TODO: Implement cart integration when products domain is available

        return $wishlistItem->delete();
    }

    /**
     * Get wishlist statistics for site.
     */
    public function getWishlistStatistics(int $siteId): array
    {
        // Get customers for this site
        $customerIds = CustomerAccount::where('site_id', $siteId)->pluck('id');

        $totalWishlistItems = Wishlist::whereIn('customer_id', $customerIds)->count();
        $customersWithWishlists = Wishlist::whereIn('customer_id', $customerIds)
            ->distinct('customer_id')
            ->count('customer_id');

        $averageItemsPerCustomer = $customersWithWishlists > 0 
            ? round($totalWishlistItems / $customersWithWishlists, 1) 
            : 0;

        $recentWishlistItems = Wishlist::whereIn('customer_id', $customerIds)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // Get most wishlisted products
        $mostWishlisted = Wishlist::whereIn('customer_id', $customerIds)
            ->select('product_id')
            ->selectRaw('COUNT(*) as wishlist_count')
            ->groupBy('product_id')
            ->orderBy('wishlist_count', 'desc')
            ->limit(10)
            ->get();

        return [
            'total_wishlist_items' => $totalWishlistItems,
            'customers_with_wishlists' => $customersWithWishlists,
            'average_items_per_customer' => $averageItemsPerCustomer,
            'recent_wishlist_items' => $recentWishlistItems,
            'most_wishlisted_products' => $mostWishlisted->toArray(),
        ];
    }
}