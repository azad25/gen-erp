<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Domain\CMS\Contracts\CartServiceInterface;
use App\Domain\CMS\DTOs\AddToCartData;
use App\Domain\CMS\DTOs\UpdateCartItemData;
use App\Domain\CMS\Models\Site;
use App\Http\Resources\ShoppingCartResource;
use App\Http\Resources\CartItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public API controller for shopping cart management.
 */
class CartController extends Controller
{
    public function __construct(
        private readonly CartServiceInterface $cartService
    ) {}

    /**
     * Get the current cart for the session/customer.
     */
    public function show(Request $request, string $tenant): JsonResponse
    {
        $site = Site::where('subdomain', $tenant)
                   ->orWhere('domain', $tenant)
                   ->firstOrFail();

        $sessionId = $request->session()->getId();
        $customerId = $request->user()?->id; // If customer is logged in

        $cart = $this->cartService->getCart($site->id, $sessionId, $customerId);
        $cart->load(['items.product', 'items.productVariant']);

        return response()->json([
            'success' => true,
            'data' => new ShoppingCartResource($cart),
        ]);
    }

    /**
     * Add an item to the cart.
     */
    public function addItem(Request $request, string $tenant): JsonResponse
    {
        $site = Site::where('subdomain', $tenant)
                   ->orWhere('domain', $tenant)
                   ->firstOrFail();

        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'quantity' => 'required|integer|min:1|max:99',
            'price' => 'required|numeric|min:0',
        ]);

        $sessionId = $request->session()->getId();
        $customerId = $request->user()?->id;

        $cart = $this->cartService->getCart($site->id, $sessionId, $customerId);

        $data = new AddToCartData(
            productId: $validated['product_id'],
            productVariantId: $validated['product_variant_id'],
            quantity: $validated['quantity'],
            price: $validated['price'],
        );

        $item = $this->cartService->addItem($cart->id, $data);
        $item->load(['product', 'productVariant']);

        return response()->json([
            'success' => true,
            'data' => new CartItemResource($item),
            'message' => 'Item added to cart successfully.',
        ], 201);
    }

    /**
     * Update the quantity of a cart item.
     */
    public function updateItem(Request $request, string $tenant, int $itemId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:99',
        ]);

        $data = new UpdateCartItemData(
            quantity: $validated['quantity']
        );

        $item = $this->cartService->updateItemQuantity($itemId, $data);
        $item->load(['product', 'productVariant']);

        return response()->json([
            'success' => true,
            'data' => new CartItemResource($item),
            'message' => 'Cart item updated successfully.',
        ]);
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(Request $request, string $tenant, int $itemId): JsonResponse
    {
        $this->cartService->removeItem($itemId);

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart successfully.',
        ]);
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(Request $request, string $tenant): JsonResponse
    {
        $site = Site::where('subdomain', $tenant)
                   ->orWhere('domain', $tenant)
                   ->firstOrFail();

        $sessionId = $request->session()->getId();
        $customerId = $request->user()?->id;

        $cart = $this->cartService->getCart($site->id, $sessionId, $customerId);
        $this->cartService->clearCart($cart->id);

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully.',
        ]);
    }

    /**
     * Get cart item count.
     */
    public function count(Request $request, string $tenant): JsonResponse
    {
        $site = Site::where('subdomain', $tenant)
                   ->orWhere('domain', $tenant)
                   ->firstOrFail();

        $sessionId = $request->session()->getId();
        $customerId = $request->user()?->id;

        $cart = $this->cartService->getCart($site->id, $sessionId, $customerId);
        $count = $this->cartService->getItemCount($cart->id);

        return response()->json([
            'success' => true,
            'data' => ['count' => $count],
        ]);
    }
}