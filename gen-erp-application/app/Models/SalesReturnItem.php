<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Line item within a sales return.
 */
class SalesReturnItem extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'sales_return_id',
        'company_id',
        'product_id',
        'variant_id',
        'description',
        'quantity',
        'unit_price',
        'line_total',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'unit_price' => 'integer',
            'line_total' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SalesReturn, $this>
     */
    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
