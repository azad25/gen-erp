<?php

namespace App\Domain\CMS\Models;

use App\Domain\CMS\Models\PublicOrder;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Factories\Domain\CMS\PublicOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Public order item model for CMS e-commerce functionality.
 * 
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property string $product_name
 * @property string $product_sku
 * @property string|null $variant_name
 * @property int $quantity
 * @property float $unit_price
 * @property float $subtotal
 * @property float $tax_amount
 * @property float $total
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property-read PublicOrder $order
 * @property-read Product $product
 * @property-read ProductVariant|null $productVariant
 */
class PublicOrderItem extends Model
{
    use HasFactory;

    protected $table = 'cms_public_order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'product_sku',
        'variant_name',
        'quantity',
        'unit_price',
        'subtotal',
        'tax_amount',
        'total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PublicOrderItemFactory
    {
        return PublicOrderItemFactory::new();
    }

    /**
     * Get the order that owns this item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(PublicOrder::class);
    }

    /**
     * Get the product for this order item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant for this order item (if any).
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the display name for this order item.
     */
    public function getDisplayName(): string
    {
        $name = $this->product_name;
        
        if ($this->variant_name) {
            $name .= ' - ' . $this->variant_name;
        }
        
        return $name;
    }

    /**
     * Get the line total (unit_price * quantity).
     */
    public function getLineTotal(): float
    {
        return $this->unit_price * $this->quantity;
    }

    /**
     * Check if this item has a variant.
     */
    public function hasVariant(): bool
    {
        return !is_null($this->product_variant_id);
    }

    /**
     * Scope to get items for a specific product.
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to get items with variants.
     */
    public function scopeWithVariants($query)
    {
        return $query->whereNotNull('product_variant_id');
    }

    /**
     * Scope to get items without variants.
     */
    public function scopeWithoutVariants($query)
    {
        return $query->whereNull('product_variant_id');
    }
}