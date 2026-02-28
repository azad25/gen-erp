<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Individual POS sale transaction.
 */
class POSSale extends Model
{
    use BelongsToCompany;

    protected $table = 'pos_sales';

    protected $fillable = [
        'company_id',
        'branch_id',
        'pos_session_id',
        'invoice_id',
        'customer_id',
        'sale_number',
        'sale_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'amount_tendered',
        'change_amount',
        'payment_method_id',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'datetime',
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'tax_amount' => 'integer',
            'total_amount' => 'integer',
            'amount_tendered' => 'integer',
            'change_amount' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (POSSale $sale): void {
            if ($sale->sale_number === null || $sale->sale_number === '') {
                $seq = static::withoutGlobalScopes()
                    ->where('company_id', $sale->company_id)
                    ->count() + 1;
                $sale->sale_number = 'POS-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }
            $sale->change_amount = ($sale->amount_tendered ?? 0) - ($sale->total_amount ?? 0);
        });
    }

    /** @return BelongsTo<POSSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(POSSession::class, 'pos_session_id');
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<POSSaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(POSSaleItem::class, 'pos_sale_id');
    }
}
