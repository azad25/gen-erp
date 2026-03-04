<?php

namespace App\Http\Controllers\Api\Public;

use App\Domain\CMS\Services\WishlistService;
use App\Domain\CMS\Models\Site;
use App\Domain\CMS\DTOs\AddToWishlistData;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\WishlistResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public API controller for customer wishlist.
 */
class WishlistController extends BaseApiController
{
    public function __construct(
        private readonly WishlistService $wishlistService
    ) {}

    /**
     * Get customer's wishlist.
     */
    public function index(Request $request, string $tenant): JsonResponse
    {
        $customerId = $this->getCustomerIdFromToken($request);
        
        if (!$customerId) {
            return $this->error('Unauthorized.', 401);
        }

        $wishlist = $this->wishlistService->getCustomerWishlist($customerId);

        return $this->success(WishlistResource::collection($wishlist));
    }

    /**
     * Add item to wishlist.
     */
    public function store(Request $request, string $tenant): JsonResponse
    {
        $customerId = $this->getCustomerIdFromToken($request);
        
        if (!$customerId) {
            return $this->error('Unauthorized.', 401);
        }

        $site = Site::where('subdomain', $tenant)
            ->orWhere('domain', $tenant)
            ->published()
            ->firstOrFail();

        $validated = $request->validate([
            'product_id' => 'required|integer',
            'product_variant_id' => 'nullable|integer',
        ]);

        try {
            $data = new AddToWishlistData(
                customerId: $customerId,
                productId: $validated['product_id'],
                productVariantId: $validated['product_variant_id'] ?? null,
            );

            $wishlistItem = $this->wishlistService->addToWishlist($data);

            return $this->success(
                new WishlistResource($wishlistItem),
                'Item added to wishlist.',
                201
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Remove item from wishlist.
     */
    public function destroy(Request $request, string $tenant, int $productId): JsonResponse
    {
        $customerId = $this->getCustomerIdFromToken($request);
        
        if (!$customerId) {
            return $this->error('Unauthorized.', 401);
        }

        $productVariantId = $request->query('variant_id');

        $removed = $this->wishlistService->removeFromWishlist(
            $customerId, 
            $productId, 
            $productVariantId ? (int) $productVariantId : null
        );

        if (!$removed) {
            return $this->error('Item not found in wishlist.', 404);
        }

        return $this->success(null, 'Item removed from wishlist.');
    }

    /**
     * Check if item is in wishlist.
     */
    public function check(Request $request, string $tenant, int $productId): JsonResponse
    {
        $customerId = $this->getCustomerIdFromToken($request);
        
        if (!$customerId) {
            return $this->error('Unauthorized.', 401);
        }

        $productVariantId = $request->query('variant_id');

        $isInWishlist = $this->wishlistService->isInWishlist(
            $customerId, 
            $productId, 
            $productVariantId ? (int) $productVariantId : null
        );

        return $this->success([
            'is_in_wishlist' => $isInWishlist,
            'product_id' => $productId,
            'product_variant_id' => $productVariantId,
        ]);
    }

    /**
     * Get wishlist item count.
     */
    public function count(Request $request, string $tenant): JsonResponse
    {
        $customerId = $this->getCustomerIdFromToken($request);
        
        if (!$customerId) {
            return $this->error('Unauthorized.', 401);
        }

        $count = $this->wishlistService->getWishlistCount($customerId);

        return $this->success(['count' => $count]);
    }

    /**
     * Clear customer's wishlist.
     */
    public function clear(Request $request, string $tenant): JsonResponse
    {
        $customerId = $this->getCustomerIdFromToken($request);
        
        if (!$customerId) {
            return $this->error('Unauthorized.', 401);
        }

        $cleared = $this->wishlistService->clearWishlist($customerId);

        if (!$cleared) {
            return $this->error('Wishlist is already empty.', 422);
        }

        return $this->success(null, 'Wishlist cleared successfully.');
    }

    /**
     * Move wishlist item to cart.
     */
    public function moveToCart(Request $request, string $tenant, int $wishlistItemId): JsonResponse
    {
        $customerId = $this->getCustomerIdFromToken($request);
        
        if (!$customerId) {
            return $this->error('Unauthorized.', 401);
        }

        $validated = $request->validate([
            'quantity' => 'nullable|integer|min:1|max:99',
        ]);

        $quantity = $validated['quantity'] ?? 1;

        try {
            $moved = $this->wishlistService->moveToCart($wishlistItemId, $quantity);

            if (!$moved) {
                return $this->error('Failed to move item to cart.', 422);
            }

            return $this->success(null, 'Item moved to cart successfully.');
        } catch (\Exception $e) {
            return $this->error('Wishlist item not found.', 404);
        }
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