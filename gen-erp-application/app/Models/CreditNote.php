<?php

namespace App\Models;

use App\Enums\CreditNoteStatus;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Credit note — reduces invoice value after issuance.
 */
class CreditNote extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'invoice_id',
        'customer_id',
        'credit_note_number',
        'credit_date',
        'reason',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => CreditNoteStatus::class,
            'credit_date' => 'date',
            'subtotal' => 'integer',
            'tax_amount' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CreditNote $cn): void {
            if ($cn->credit_note_number === null) {
                $date = now()->format('Ymd');
                $seq = static::withoutGlobalScopes()
                    ->where('company_id', $cn->company_id)
                    ->count() + 1;
                $cn->credit_note_number = 'CN-'.$date.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function workflowDocumentType(): string
    {
        return 'credit_note';
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<CreditNoteItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }
}
