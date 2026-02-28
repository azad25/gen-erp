<?php

namespace App\Models;

use App\Enums\StockTransferStatus;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Stock transfer between two warehouses within a company.
 */
class StockTransfer extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'reference_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'notes',
        'transferred_by',
        'received_by',
        'transfer_date',
        'received_date',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => StockTransferStatus::class,
            'transfer_date' => 'date',
            'received_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (StockTransfer $transfer): void {
            if ($transfer->reference_number === null) {
                $date = now()->format('Ymd');
                $seq = static::withoutGlobalScopes()
                    ->where('company_id', $transfer->company_id)
                    ->count() + 1;
                $transfer->reference_number = "TRF-{$date}-".str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function workflowDocumentType(): string
    {
        return 'stock_transfer';
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * @return HasMany<StockTransferItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
