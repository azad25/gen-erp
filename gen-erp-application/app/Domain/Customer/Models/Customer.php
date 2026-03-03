<?php

namespace App\Domain\Customer\Models;

use App\Models\ContactGroup;
use App\Domain\Customer\Models\CustomerTransaction;
use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use App\Domain\Auth\Models\Concerns\DispatchesModelEvents;
use App\Domain\Auth\Models\Concerns\HasCustomFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Customer contact with credit management and transaction ledger.
 */
class Customer extends Model
{
    use BelongsToCompany;
    use DispatchesModelEvents;
    use HasCustomFields;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'group_id',
        'customer_code',
        'name',
        'email',
        'phone',
        'mobile',
        'address_line1',
        'address_line2',
        'city',
        'district',
        'postal_code',
        'vat_bin',
        'credit_limit',
        'credit_days',
        'opening_balance',
        'opening_balance_date',
        'notes',
        'custom_fields',
        'is_active',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\CustomerFactory
    {
        return \Database\Factories\CustomerFactory::new();
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'credit_limit' => 'integer',
            'credit_days' => 'integer',
            'opening_balance' => 'integer',
            'opening_balance_date' => 'date',
            'is_active' => 'boolean',
            'custom_fields' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Customer $customer): void {
            if ($customer->customer_code === null) {
                $maxId = static::withoutGlobalScopes()
                    ->where('company_id', $customer->company_id)
                    ->max('id');
                $next = ($maxId ?? 0) + 1;
                $customer->customer_code = 'CUST-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function customFieldEntityType(): string
    {
        return 'customer';
    }

    public function alertEntityType(): string
    {
        return 'customer';
    }

    /**
     * @return BelongsTo<ContactGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ContactGroup::class, 'group_id');
    }

    /**
     * @return HasMany<CustomerTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CustomerTransaction::class);
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
     * Current balance = opening_balance + invoiced - paid (via transactions).
     */
    public function currentBalance(): int
    {
        $txnSum = $this->transactions()
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->value('total');

        return $this->opening_balance + (int) $txnSum;
    }

    /**
     * Whether the current balance exceeds the credit limit.
     */
    public function isOverCreditLimit(): bool
    {
        if ($this->credit_limit <= 0) {
            return false;
        }

        return $this->currentBalance() > $this->credit_limit;
    }

    /**
     * Formatted balance string for display.
     */
    public function formattedBalance(): string
    {
        $balance = $this->currentBalance();
        $formatted = '৳'.number_format(abs($balance) / 100, 2);

        return $balance < 0 ? "-{$formatted}" : $formatted;
    }
}
