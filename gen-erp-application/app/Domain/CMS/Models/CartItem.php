<?php

namespace App\Domain\CMS\Models;

use App\Domain\CMS\Models\ShoppingCart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Cart item model for CMS e-commerce functionality.
 * 
 * @property int $id
 * @property int $cart_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int $quantity
 * @property float $price
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property-read ShoppingCart $cart
 * @property-read Product $product
 * @property-read ProductVariant|null $productVariant
 */
class CartItem extends Model
{
    use HasFactory;

    protected $table = 'cms_cart_items';

    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
    ];

    /**
     * Get the cart that owns this item.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(ShoppingCart::class);
    }

    /**
     * Get the product for this cart item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant for this cart item (if any).
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the total price for this cart item (price * quantity).
     */
    public function getTotal(): float
    {
        return $this->price * $this->quantity;
    }

    /**
     * Get the display name for this cart item.
     */
    public function getDisplayName(): string
    {
        $name = $this->product->name;
        
        if ($this->productVariant) {
            $name .= ' - ' . $this->productVariant->name;
        }
        
        return $name;
    }

    /**
     * Get the SKU for this cart item.
     */
    public function getSku(): string
    {
        return $this->productVariant?->sku ?? $this->product->sku;
    }

    /**
     * Check if the product is still available in the requested quantity.
     */
    public function isAvailable(): bool
    {
        if ($this->productVariant) {
            return $this->productVariant->stock_quantity >= $this->quantity;
        }
        
        return $this->product->stock_quantity >= $this->quantity;
    }

    /**
     * Get the available stock quantity for this item.
     */
    public function getAvailableStock(): int
    {
        if ($this->productVariant) {
            return $this->productVariant->stock_quantity;
        }
        
        return $this->product->stock_quantity;
    }

    /**
     * Update the quantity for this cart item.
     */
    public function updateQuantity(int $quantity): void
    {
        $this->quantity = max(1, $quantity);
        $this->save();
    }

    /**
     * Increment the quantity by the specified amount.
     */
    public function incrementQuantity(int $amount = 1): void
    {
        $this->quantity += $amount;
        $this->save();
    }

    /**
     * Decrement the quantity by the specified amount.
     */
    public function decrementQuantity(int $amount = 1): void
    {
        $this->quantity = max(1, $this->quantity - $amount);
        $this->save();
    }

    /**
     * Scope to get items for a specific product.
     */
    public function scopeForProduct($query, int $productId, ?int $variantId = null)
    {
        return $query->where('product_id', $productId)
                    ->where('product_variant_id', $variantId);
    }
}