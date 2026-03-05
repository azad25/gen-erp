<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Records how a stock-out movement consumed units from a specific layer.
 * This is the COGS audit trail — every unit sold traces to a purchase cost.
 *
 * Immutable: once created, allocations cannot be modified or deleted
 * (they represent historical COGS facts).
 */
class StockLayerAllocation extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'stock_layer_id',
        'stock_movement_id',
        'quantity',
        'unit_cost',
        'cost_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'unit_cost' => 'integer',
            'cost_amount' => 'integer',
        ];
    }

    /**
     * Prevent modification of allocation records (immutability).
     */
    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException(__('Stock layer allocations are immutable and cannot be modified.'));
        });

        static::deleting(function (): void {
            throw new RuntimeException(__('Stock layer allocations are immutable and cannot be deleted.'));
        });
    }

    /**
     * @return BelongsTo<StockLayer, $this>
     */
    public function layer(): BelongsTo
    {
        return $this->belongsTo(StockLayer::class, 'stock_layer_id');
    }
    
    /**
     * @return BelongsTo<StockLayer, $this>
     */
    public function stockLayer(): BelongsTo
    {
        return $this->belongsTo(StockLayer::class, 'stock_layer_id');
    }

    /**
     * @return BelongsTo<StockMovement, $this>
     */
    public function movement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'stock_movement_id');
    }
    
    /**
     * @return BelongsTo<StockMovement, $this>
     */
    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'stock_movement_id');
    }
}
