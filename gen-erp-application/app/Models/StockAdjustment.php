<?php

namespace App\Models;

use App\Enums\AdjustmentReason;
use App\Enums\StockAdjustmentStatus;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Stock adjustment document with workflow approval support.
 */
class StockAdjustment extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'reference_number',
        'reason',
        'notes',
        'status',
        'adjusted_by',
        'approved_by',
        'adjustment_date',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'reason' => AdjustmentReason::class,
            'status' => StockAdjustmentStatus::class,
            'adjustment_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (StockAdjustment $adj): void {
            if ($adj->reference_number === null) {
                $date = now()->format('Ymd');
                $seq = static::withoutGlobalScopes()
                    ->where('company_id', $adj->company_id)
                    ->count() + 1;
                $adj->reference_number = "ADJ-{$date}-".str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function workflowDocumentType(): string
    {
        return 'stock_adjustment';
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<StockAdjustmentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }
}
