<?php

namespace App\Models;

use App\Enums\StockMovementType;
use App\Models\Traits\BelongsToCompany;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sales return — customer returns goods, stock is restored.
 */
class SalesReturn extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'invoice_id',
        'customer_id',
        'warehouse_id',
        'return_number',
        'return_date',
        'reason',
        'total_amount',
        'status',
        'stock_restored',
        'created_by',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'total_amount' => 'integer',
            'stock_restored' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesReturn $return): void {
            if ($return->return_number === null) {
                $date = now()->format('Ymd');
                $seq = static::withoutGlobalScopes()
                    ->where('company_id', $return->company_id)
                    ->count() + 1;
                $return->return_number = 'SR-'.$date.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function workflowDocumentType(): string
    {
        return 'sales_return';
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return HasMany<SalesReturnItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    /**
     * Restore stock for returned items via InventoryService.
     */
    public function restoreStock(): void
    {
        if ($this->stock_restored) {
            return;
        }

        $inventoryService = app(InventoryService::class);

        foreach ($this->items as $item) {
            if ($item->product_id === null) {
                continue;
            }

            $product = Product::withoutGlobalScopes()->find($item->product_id);
            if ($product === null || ! $product->track_inventory) {
                continue;
            }

            $inventoryService->stockIn(
                $this->warehouse_id,
                $item->product_id,
                (float) $item->quantity,
                StockMovementType::SALE_RETURN,
                $item->variant_id,
                $item->unit_price,
                "SR {$this->return_number}",
                $this,
            );
        }

        $this->update(['stock_restored' => true]);
    }
}
