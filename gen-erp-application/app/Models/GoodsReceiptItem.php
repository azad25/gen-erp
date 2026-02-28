<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Line item within a goods receipt.
 */
class GoodsReceiptItem extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'goods_receipt_id',
        'company_id',
        'purchase_order_item_id',
        'product_id',
        'variant_id',
        'description',
        'quantity_received',
        'unit',
        'unit_cost',
        'tax_rate',
        'tax_amount',
        'line_total',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'quantity_received' => 'float',
            'unit_cost' => 'integer',
            'tax_rate' => 'float',
            'tax_amount' => 'integer',
            'line_total' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<GoodsReceipt, $this>
     */
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    /**
     * @return BelongsTo<PurchaseOrderItem, $this>
     */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
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
}
