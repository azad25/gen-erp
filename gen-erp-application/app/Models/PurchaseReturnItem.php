<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Line item within a purchase return.
 */
class PurchaseReturnItem extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'purchase_return_id',
        'company_id',
        'product_id',
        'variant_id',
        'description',
        'quantity',
        'unit_cost',
        'line_total',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'unit_cost' => 'integer',
            'line_total' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PurchaseReturn, $this>
     */
    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
