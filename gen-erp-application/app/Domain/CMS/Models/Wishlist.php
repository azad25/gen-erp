<?php

namespace App\Domain\CMS\Models;

use App\Domain\CMS\Models\CustomerAccount;
use Database\Factories\Domain\CMS\WishlistFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Wishlist model for CMS e-commerce functionality.
 * 
 * @property int $id
 * @property int $customer_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property-read CustomerAccount $customer
 */
class Wishlist extends Model
{
    use HasFactory;

    protected $table = 'cms_wishlists';

    protected $fillable = [
        'customer_id',
        'product_id',
        'product_variant_id',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'product_id' => 'integer',
        'product_variant_id' => 'integer',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WishlistFactory
    {
        return WishlistFactory::new();
    }

    /**
     * Get the customer who owns this wishlist item.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class);
    }

    /**
     * Get the product in this wishlist item.
     * Note: This will reference the products table when it exists.
     */
    public function product()
    {
        // This will be implemented when products table exists
        // return $this->belongsTo(Product::class);
        return null;
    }

    /**
     * Get the product variant in this wishlist item.
     * Note: This will reference the product_variants table when it exists.
     */
    public function productVariant()
    {
        // This will be implemented when product_variants table exists
        // return $this->belongsTo(ProductVariant::class);
        return null;
    }

    /**
     * Check if this wishlist item has a variant.
     */
    public function hasVariant(): bool
    {
        return !is_null($this->product_variant_id);
    }

    /**
     * Scope to get wishlist items for a specific customer.
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope to get wishlist items for a specific product.
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to get wishlist items with variants.
     */
    public function scopeWithVariants($query)
    {
        return $query->whereNotNull('product_variant_id');
    }

    /**
     * Scope to get wishlist items without variants.
     */
    public function scopeWithoutVariants($query)
    {
        return $query->whereNull('product_variant_id');
    }

    /**
     * Scope to order by newest.
     */
    public function scopeOrderByNewest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to order by oldest.
     */
    public function scopeOrderByOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }
}