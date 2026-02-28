<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Line item within a stock transfer.
 */
class StockTransferItem extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'stock_transfer_id',
        'company_id',
        'product_id',
        'variant_id',
        'quantity_sent',
        'quantity_received',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'quantity_sent' => 'float',
            'quantity_received' => 'float',
        ];
    }

    /**
     * @return BelongsTo<StockTransfer, $this>
     */
    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
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
