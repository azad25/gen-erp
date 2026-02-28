<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Payment made to a supplier with TDS/VDS deductions.
 */
class SupplierPayment extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'supplier_id',
        'payment_method_id',
        'payment_number',
        'payment_date',
        'gross_amount',
        'tds_amount',
        'vds_amount',
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
            'gross_amount' => 'integer',
            'tds_amount' => 'integer',
            'vds_amount' => 'integer',
            'net_amount' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SupplierPayment $payment): void {
            if ($payment->payment_number === null) {
                $date = now()->format('Ymd');
                $seq = static::withoutGlobalScopes()
                    ->where('company_id', $payment->company_id)
                    ->count() + 1;
                $payment->payment_number = 'PAY-'.$date.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * @return HasMany<SupplierPaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(SupplierPaymentAllocation::class);
    }
}
