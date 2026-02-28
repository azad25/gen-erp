<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An invoice generated for a subscription payment.
 */
class SubscriptionInvoice extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'subscription_id',
        'payment_request_id',
        'invoice_number',
        'amount',
        'billing_cycle',
        'period_start',
        'period_end',
        'paid_at',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return BelongsTo<PaymentRequest, $this> */
    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    /** Formatted amount in BDT. */
    public function formattedAmount(): string
    {
        return '৳' . number_format($this->amount / 100, 2);
    }
}
