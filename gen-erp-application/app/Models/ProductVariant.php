<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Product variant — overrides parent pricing when set.
 */
class ProductVariant extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'product_id',
        'name',
        'sku',
        'barcode',
        'cost_price',
        'selling_price',
        'attributes',
        'is_active',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'cost_price' => 'integer',
            'selling_price' => 'integer',
            'attributes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Variant's cost price, falling back to parent product's.
     */
    public function effectiveCostPrice(): int
    {
        return $this->cost_price ?? $this->product->cost_price;
    }

    /**
     * Variant's selling price, falling back to parent product's.
     */
    public function effectiveSellingPrice(): int
    {
        return $this->selling_price ?? $this->product->selling_price;
    }
}
