<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Current stock quantity for a product/variant at a specific warehouse.
 */
class StockLevel extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'product_id',
        'variant_id',
        'quantity',
        'reserved_quantity',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'reserved_quantity' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Available = on-hand minus reserved.
     */
    public function availableQuantity(): float
    {
        return $this->quantity - $this->reserved_quantity;
    }

    /**
     * Whether available stock is at or below the product's low-stock threshold.
     */
    public function isLowStock(): bool
    {
        $threshold = $this->product?->low_stock_threshold ?? 0;

        if ($threshold <= 0) {
            return false;
        }

        return $this->availableQuantity() <= $threshold;
    }
}
