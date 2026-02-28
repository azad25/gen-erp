<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Allocation of a customer payment against a specific invoice.
 */
class CustomerPaymentAllocation extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'customer_payment_id',
        'company_id',
        'invoice_id',
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
     * @return BelongsTo<CustomerPayment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(CustomerPayment::class, 'customer_payment_id');
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
