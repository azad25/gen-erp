<?php

namespace App\Domain\CMS\Services;

use App\Domain\CMS\Contracts\CartServiceInterface;
use App\Domain\CMS\Models\ShoppingCart;
use App\Domain\CMS\Models\CartItem;
use App\Domain\CMS\Models\PublicOrder;
use App\Domain\CMS\Models\PublicOrderItem;
use App\Domain\CMS\DTOs\AddToCartData;
use App\Domain\CMS\DTOs\UpdateCartItemData;
use App\Domain\CMS\DTOs\CreateOrderData;
use App\Domain\CMS\Enums\OrderStatus;
use App\Domain\CMS\Enums\PaymentStatus;
use App\Domain\CMS\Events\CartItemAdded;
use App\Domain\CMS\Events\OrderPlaced;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Service for managing shopping carts and cart items.
 */
class CartService implements CartServiceInterface
{
    /**
     * Get or create a cart for the given session/customer.
     */
    public function getCart(int $siteId, string $sessionId, ?int $customerId = null): ShoppingCart
    {
        // First try to find existing cart
        $query = ShoppingCart::where('site_id', $siteId)->active();
        
        if ($customerId) {
            $cart = $query->where('customer_id', $customerId)->first();
        } else {
            $cart = $query->where('session_id', $sessionId)->first();
        }
        
        // Create new cart if not found
        if (!$cart) {
            $cart = ShoppingCart::create([
                'site_id' => $siteId,
                'session_id' => $customerId ? null : $sessionId,
                'customer_id' => $customerId,
                'expires_at' => $customerId ? null : now()->addHours(24),
            ]);
        }
        
        return $cart;
    }

    /**
     * Add an item to the cart.
     */
    public function addItem(int $cartId, AddToCartData $data): CartItem
    {
        $cart = ShoppingCart::findOrFail($cartId);
        
        // Check if item already exists in cart
        $existingItem = $cart->items()
            ->forProduct($data->productId, $data->productVariantId)
            ->first();
        
        if ($existingItem) {
            // Update quantity if item exists
            $existingItem->incrementQuantity($data->quantity);
            $item = $existingItem;
        } else {
            // Create new cart item
            $item = $cart->items()->create($data->toArray());
        }
        
        // Dispatch event
        event(new CartItemAdded($item));
        
        return $item;
    }

    /**
     * Update the quantity of a cart item.
     */
    public function updateItemQuantity(int $itemId, UpdateCartItemData $data): CartItem
    {
        $item = CartItem::findOrFail($itemId);
        $item->updateQuantity($data->quantity);
        
        return $item;
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(int $itemId): bool
    {
        $item = CartItem::findOrFail($itemId);
        return $item->delete();
    }

    /**
     * Clear all items from the cart.
     */
    public function clearCart(int $cartId): bool
    {
        $cart = ShoppingCart::findOrFail($cartId);
        $cart->clear();
        
        return true;
    }

    /**
     * Get cart totals breakdown.
     */
    public function getCartTotal(int $cartId): array
    {
        $cart = ShoppingCart::findOrFail($cartId);
        return $cart->getTotals();
    }

    /**
     * Get the total number of items in the cart.
     */
    public function getItemCount(int $cartId): int
    {
        $cart = ShoppingCart::findOrFail($cartId);
        return $cart->getItemCount();
    }

    /**
     * Convert cart to order.
     */
    public function convertToOrder(int $cartId, CreateOrderData $data): PublicOrder
    {
        return DB::transaction(function () use ($cartId, $data) {
            $cart = ShoppingCart::with('items.product', 'items.productVariant')->findOrFail($cartId);
            
            if ($cart->isEmpty()) {
                throw new \InvalidArgumentException('Cannot create order from empty cart');
            }
            
            // Generate order number
            $orderNumber = $this->generateOrderNumber();
            
            // Create order
            $orderData = array_merge($data->toArray(), [
                'order_number' => $orderNumber,
                'status' => OrderStatus::PENDING->value,
                'payment_status' => PaymentStatus::PENDING->value,
                'placed_at' => now(),
            ]);
            
            $order = PublicOrder::create($orderData);
            
            // Create order items from cart items
            foreach ($cart->items as $cartItem) {
                $this->createOrderItem($order, $cartItem);
            }
            
            // Clear the cart
            $cart->clear();
            
            // Dispatch event
            event(new OrderPlaced($order));
            
            return $order;
        });
    }

    /**
     * Clean up expired carts.
     */
    public function cleanupExpiredCarts(): int
    {
        $expiredCarts = ShoppingCart::expired()->get();
        $count = $expiredCarts->count();
        
        foreach ($expiredCarts as $cart) {
            $cart->items()->delete();
            $cart->delete();
        }
        
        return $count;
    }

    /**
     * Transfer cart from session to customer.
     */
    public function transferCartToCustomer(string $sessionId, int $customerId): ?ShoppingCart
    {
        $sessionCart = ShoppingCart::bySession($sessionId)->active()->first();
        
        if (!$sessionCart) {
            return null;
        }
        
        // Check if customer already has a cart
        $customerCart = ShoppingCart::byCustomer($customerId)->active()->first();
        
        if ($customerCart) {
            // Merge session cart items into customer cart
            foreach ($sessionCart->items as $item) {
                $existingItem = $customerCart->items()
                    ->forProduct($item->product_id, $item->product_variant_id)
                    ->first();
                
                if ($existingItem) {
                    $existingItem->incrementQuantity($item->quantity);
                } else {
                    $customerCart->items()->create([
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ]);
                }
            }
            
            // Delete session cart
            $sessionCart->items()->delete();
            $sessionCart->delete();
            
            return $customerCart;
        } else {
            // Transfer session cart to customer
            $sessionCart->update([
                'customer_id' => $customerId,
                'session_id' => null,
                'expires_at' => null,
            ]);
            
            return $sessionCart;
        }
    }

    /**
     * Generate a unique order number.
     */
    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . strtoupper(Str::random(8));
        } while (PublicOrder::where('order_number', $orderNumber)->exists());
        
        return $orderNumber;
    }

    /**
     * Create an order item from a cart item.
     */
    private function createOrderItem(PublicOrder $order, CartItem $cartItem): PublicOrderItem
    {
        $product = $cartItem->product;
        $variant = $cartItem->productVariant;
        
        $unitPrice = $cartItem->price;
        $quantity = $cartItem->quantity;
        $subtotal = $unitPrice * $quantity;
        $taxAmount = 0; // TODO: Implement tax calculation
        $total = $subtotal + $taxAmount;
        
        return $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'product_name' => $product->name,
            'product_sku' => $variant?->sku ?? $product->sku,
            'variant_name' => $variant?->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);
    }
}