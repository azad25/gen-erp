<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Line item within a stock adjustment.
 */
class StockAdjustmentItem extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'stock_adjustment_id',
        'company_id',
        'product_id',
        'variant_id',
        'warehouse_id',
        'current_quantity',
        'adjusted_quantity',
        'unit_cost',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'current_quantity' => 'float',
            'adjusted_quantity' => 'float',
            'unit_cost' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<StockAdjustment, $this>
     */
    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
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
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
