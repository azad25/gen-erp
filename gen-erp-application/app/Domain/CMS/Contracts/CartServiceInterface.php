<?php

namespace App\Domain\CMS\Contracts;

use App\Domain\CMS\Models\ShoppingCart;
use App\Domain\CMS\Models\CartItem;
use App\Domain\CMS\Models\PublicOrder;
use App\Domain\CMS\DTOs\AddToCartData;
use App\Domain\CMS\DTOs\UpdateCartItemData;
use App\Domain\CMS\DTOs\CreateOrderData;

/**
 * Contract for cart management service.
 */
interface CartServiceInterface
{
    /**
     * Get or create a cart for the given session/customer.
     */
    public function getCart(int $siteId, string $sessionId, ?int $customerId = null): ShoppingCart;

    /**
     * Add an item to the cart.
     */
    public function addItem(int $cartId, AddToCartData $data): CartItem;

    /**
     * Update the quantity of a cart item.
     */
    public function updateItemQuantity(int $itemId, UpdateCartItemData $data): CartItem;

    /**
     * Remove an item from the cart.
     */
    public function removeItem(int $itemId): bool;

    /**
     * Clear all items from the cart.
     */
    public function clearCart(int $cartId): bool;

    /**
     * Get cart totals breakdown.
     */
    public function getCartTotal(int $cartId): array;

    /**
     * Get the total number of items in the cart.
     */
    public function getItemCount(int $cartId): int;

    /**
     * Convert cart to order.
     */
    public function convertToOrder(int $cartId, CreateOrderData $data): PublicOrder;

    /**
     * Clean up expired carts.
     */
    public function cleanupExpiredCarts(): int;

    /**
     * Transfer cart from session to customer.
     */
    public function transferCartToCustomer(string $sessionId, int $customerId): ?ShoppingCart;
}