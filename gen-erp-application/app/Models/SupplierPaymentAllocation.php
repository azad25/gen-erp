<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Allocation of a supplier payment against a purchase order.
 */
class SupplierPaymentAllocation extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'supplier_payment_id',
        'company_id',
        'purchase_order_id',
        'allocated_amount',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'allocated_amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SupplierPayment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(SupplierPayment::class, 'supplier_payment_id');
    }

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
