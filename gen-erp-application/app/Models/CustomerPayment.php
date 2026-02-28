<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Payment received from a customer.
 */
class CustomerPayment extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'payment_method_id',
        'receipt_number',
        'payment_date',
        'amount',
        'reference_number',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CustomerPayment $payment): void {
            if ($payment->receipt_number === null) {
                $date = now()->format('Ymd');
                $seq = static::withoutGlobalScopes()
                    ->where('company_id', $payment->company_id)
                    ->count() + 1;
                $payment->receipt_number = 'RCP-'.$date.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * @return HasMany<CustomerPaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(CustomerPaymentAllocation::class);
    }

    /**
     * Amount not yet allocated to any invoice.
     */
    public function unallocatedAmount(): int
    {
        return $this->amount - (int) $this->allocations()->sum('allocated_amount');
    }
}
