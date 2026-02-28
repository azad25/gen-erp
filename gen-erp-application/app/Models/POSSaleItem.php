<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Line item within a POS sale.
 */
class POSSaleItem extends Model
{
    use BelongsToCompany;

    protected $table = 'pos_sale_items';

    protected $fillable = [
        'pos_sale_id',
        'company_id',
        'product_id',
        'variant_id',
        'description',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'integer',
            'discount_amount' => 'integer',
            'tax_amount' => 'integer',
            'line_total' => 'integer',
        ];
    }

    /** @return BelongsTo<POSSale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(POSSale::class, 'pos_sale_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
