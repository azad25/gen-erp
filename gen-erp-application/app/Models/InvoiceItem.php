<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Line item within an invoice.
 */
class InvoiceItem extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'invoice_id',
        'company_id',
        'product_id',
        'variant_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
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
            'quantity' => 'float',
            'unit_price' => 'integer',
            'discount_percent' => 'float',
            'discount_amount' => 'integer',
            'tax_rate' => 'float',
            'tax_amount' => 'integer',
            'line_total' => 'integer',
            'display_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
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
     * Line total before tax.
     */
    public function lineTotalWithoutTax(): int
    {
        return $this->line_total - $this->tax_amount;
    }
}
