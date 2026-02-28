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
 * Purchase return — return goods to supplier, stock is removed.
 */
class PurchaseReturn extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'goods_receipt_id',
        'supplier_id',
        'warehouse_id',
        'return_number',
        'return_date',
        'reason',
        'total_amount',
        'status',
        'stock_removed',
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
            'stock_removed' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PurchaseReturn $return): void {
            if ($return->return_number === null) {
                $date = now()->format('Ymd');
                $seq = static::withoutGlobalScopes()
                    ->where('company_id', $return->company_id)
                    ->count() + 1;
                $return->return_number = 'PR-'.$date.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function workflowDocumentType(): string
    {
        return 'purchase_return';
    }

    /**
     * @return BelongsTo<GoodsReceipt, $this>
     */
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return HasMany<PurchaseReturnItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    /**
     * Remove stock for returned items via InventoryService.
     */
    public function removeStock(): void
    {
        if ($this->stock_removed) {
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

            $inventoryService->stockOut(
                $this->warehouse_id,
                $item->product_id,
                (float) $item->quantity,
                StockMovementType::PURCHASE_RETURN,
                $item->variant_id,
                "PR {$this->return_number}",
                $this,
            );
        }

        $this->update(['stock_removed' => true]);
    }
}
