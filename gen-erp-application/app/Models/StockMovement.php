<?php

namespace App\Models;

use App\Enums\StockMovementType;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;

/**
 * Immutable stock movement ledger entry.
 */
class StockMovement extends Model
{
    use BelongsToCompany;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'product_id',
        'variant_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'notes',
        'moved_by',
        'movement_date',
        'created_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'movement_type' => StockMovementType::class,
            'quantity' => 'float',
            'quantity_before' => 'float',
            'quantity_after' => 'float',
            'unit_cost' => 'integer',
            'movement_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException(__('Stock movements are immutable and cannot be updated.'));
        });

        static::deleting(function (): void {
            throw new RuntimeException(__('Stock movements are immutable and cannot be deleted.'));
        });
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
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
