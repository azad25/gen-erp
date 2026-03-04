<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A stock layer represents a batch of inventory received at a specific cost.
 * Layers are consumed FIFO (oldest first) during stock-out operations.
 */
class StockLayer extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'product_id',
        'variant_id',
        'source_movement_id',
        'quantity_in',
        'quantity_remaining',
        'unit_cost',
        'layer_date',
    ];

    protected function casts(): array
    {
        return [
            'quantity_in' => 'float',
            'quantity_remaining' => 'float',
            'unit_cost' => 'integer',
            'layer_date' => 'date',
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
        return $this->belongsTo(\App\Domain\Product\Models\Product::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * @return BelongsTo<StockMovement, $this>
     */
    public function sourceMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'source_movement_id');
    }

    /**
     * @return HasMany<StockLayerAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(StockLayerAllocation::class);
    }

    /**
     * Whether this layer has been fully consumed.
     */
    public function isExhausted(): bool
    {
        return $this->quantity_remaining <= 0;
    }

    /**
     * Total value remaining in this layer.
     */
    public function remainingValue(): int
    {
        return (int) round($this->quantity_remaining * $this->unit_cost);
    }
}
