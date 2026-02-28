<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Line item within a purchase order.
 */
class PurchaseOrderItem extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'purchase_order_id',
        'company_id',
        'product_id',
        'variant_id',
        'description',
        'quantity_ordered',
        'quantity_received',
        'unit',
        'unit_cost',
        'discount_percent',
        'discount_amount',
        'tax_group_id',
        'tax_rate',
        'tax_amount',
        'line_total',
        'display_order',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'float',
            'quantity_received' => 'float',
            'unit_cost' => 'integer',
            'discount_percent' => 'float',
            'discount_amount' => 'integer',
            'tax_rate' => 'float',
            'tax_amount' => 'integer',
            'line_total' => 'integer',
            'display_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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
     * @return BelongsTo<TaxGroup, $this>
     */
    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroup::class);
    }

    /**
     * Remaining quantity to be received.
     */
    public function remainingQuantity(): float
    {
        return $this->quantity_ordered - $this->quantity_received;
    }
}
