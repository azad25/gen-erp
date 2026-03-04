<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Auth\Models\Company;
use App\Domain\Product\Models\Product;
use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use App\Support\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory, BelongsToCompany;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'idempotency_key',
        'warehouse_id',
        'product_id',
        'variant_id',
        'movement_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'notes',
        'movement_date',
    ];

    protected $casts = [
        'movement_type' => StockMovementType::class,
        'quantity' => 'float',
        'quantity_before' => 'float',
        'quantity_after' => 'float',
        'unit_cost' => 'integer',
        'total_cost' => 'integer',
        'movement_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockLevel(): BelongsTo
    {
        return $this->belongsTo(StockLevel::class, 'warehouse_id', 'warehouse_id')
            ->where('product_id', $this->product_id);
    }

    /**
     * Prevent updates to stock movements for data integrity.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('Stock movements are immutable and cannot be updated.');
        });
    }
}