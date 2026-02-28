<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use App\Models\Traits\DispatchesModelEvents;
use App\Models\Traits\HasCustomFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Supplier contact with TDS/VDS deduction support and bank details.
 */
class Supplier extends Model
{
    use BelongsToCompany;
    use DispatchesModelEvents;
    use HasCustomFields;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'group_id',
        'supplier_code',
        'name',
        'contact_person',
        'email',
        'phone',
        'mobile',
        'address_line1',
        'address_line2',
        'city',
        'district',
        'postal_code',
        'vat_bin',
        'tds_rate',
        'vds_rate',
        'credit_days',
        'opening_balance',
        'opening_balance_date',
        'bank_name',
        'bank_account_number',
        'bank_routing_number',
        'notes',
        'custom_fields',
        'is_active',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'tds_rate' => 'float',
            'vds_rate' => 'float',
            'credit_days' => 'integer',
            'opening_balance' => 'integer',
            'opening_balance_date' => 'date',
            'is_active' => 'boolean',
            'custom_fields' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Supplier $supplier): void {
            if ($supplier->supplier_code === null) {
                $maxId = static::withoutGlobalScopes()
                    ->where('company_id', $supplier->company_id)
                    ->max('id');
                $next = ($maxId ?? 0) + 1;
                $supplier->supplier_code = 'SUPP-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function customFieldEntityType(): string
    {
        return 'supplier';
    }

    public function alertEntityType(): string
    {
        return 'supplier';
    }

    /**
     * @return BelongsTo<ContactGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ContactGroup::class, 'group_id');
    }

    /**
     * @return HasMany<SupplierTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(SupplierTransaction::class);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Current balance = opening_balance + billed - paid.
     */
    public function currentBalance(): int
    {
        $txnSum = $this->transactions()
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->value('total');

        return $this->opening_balance + (int) $txnSum;
    }

    /**
     * Calculate net payment after TDS/VDS deductions.
     *
     * @return array{net: int, tds_amount: int, vds_amount: int}
     */
    public function netPaymentAmount(int $grossAmount): array
    {
        $tdsAmount = (int) round($grossAmount * ($this->tds_rate / 100));
        $vdsAmount = (int) round($grossAmount * ($this->vds_rate / 100));
        $net = $grossAmount - $tdsAmount - $vdsAmount;

        return [
            'net' => $net,
            'tds_amount' => $tdsAmount,
            'vds_amount' => $vdsAmount,
        ];
    }
}
