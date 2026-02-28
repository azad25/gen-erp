<?php

namespace App\Models;

use App\Enums\GoodsReceiptStatus;
use App\Enums\StockMovementType;
use App\Models\Traits\BelongsToCompany;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Goods receipt note (GRN) — records physical receipt of purchased goods.
 */
class GoodsReceipt extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'purchase_order_id',
        'supplier_id',
        'warehouse_id',
        'receipt_number',
        'supplier_invoice_number',
        'supplier_invoice_date',
        'receipt_date',
        'status',
        'subtotal',
        'tax_amount',
        'total_amount',
        'notes',
        'stock_added',
        'created_by',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => GoodsReceiptStatus::class,
            'supplier_invoice_date' => 'date',
            'receipt_date' => 'date',
            'subtotal' => 'integer',
            'tax_amount' => 'integer',
            'total_amount' => 'integer',
            'stock_added' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GoodsReceipt $receipt): void {
            if ($receipt->receipt_number === null) {
                $date = now()->format('Ymd');
                $seq = static::withoutGlobalScopes()
                    ->where('company_id', $receipt->company_id)
                    ->count() + 1;
                $receipt->receipt_number = 'GRN-'.$date.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function workflowDocumentType(): string
    {
        return 'goods_receipt';
    }

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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
     * @return HasMany<GoodsReceiptItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    /**
     * Add stock for each item via InventoryService.
     */
    public function addStock(): void
    {
        if ($this->stock_added) {
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
                (float) $item->quantity_received,
                StockMovementType::PURCHASE_RECEIPT,
                $item->variant_id,
                $item->unit_cost,
                "GRN {$this->receipt_number}",
                $this,
            );
        }

        $this->update(['stock_added' => true]);
    }
}
