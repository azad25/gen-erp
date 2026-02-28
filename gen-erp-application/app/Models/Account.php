<?php

namespace App\Models;

use App\Enums\AccountSubType;
use App\Enums\AccountType;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Chart of Accounts entry — represents a ledger account.
 */
class Account extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'account_group_id',
        'code',
        'name',
        'account_type',
        'sub_type',
        'opening_balance',
        'opening_balance_date',
        'is_system',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'account_type' => AccountType::class,
            'sub_type' => AccountSubType::class,
            'opening_balance' => 'integer',
            'opening_balance_date' => 'date',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<AccountGroup, $this> */
    public function accountGroup(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class);
    }

    /** @return HasMany<JournalEntryLine, $this> */
    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Normal balance side: debit for assets/expenses, credit for liabilities/equity/income.
     */
    public function normalBalanceSide(): string
    {
        return match ($this->account_type) {
            AccountType::ASSET, AccountType::EXPENSE => 'debit',
            AccountType::LIABILITY, AccountType::EQUITY, AccountType::INCOME => 'credit',
        };
    }

    /**
     * Current balance from posted journal entries + opening balance.
     */
    public function currentBalance(): int
    {
        $debits = $this->journalLines()
            ->whereHas('journalEntry', fn (Builder $q) => $q->where('status', 'posted'))
            ->sum('debit');

        $credits = $this->journalLines()
            ->whereHas('journalEntry', fn (Builder $q) => $q->where('status', 'posted'))
            ->sum('credit');

        $netMovement = $this->normalBalanceSide() === 'debit'
            ? $debits - $credits
            : $credits - $debits;

        return $this->opening_balance + $netMovement;
    }

    public function formattedBalance(): string
    {
        return '৳'.number_format($this->currentBalance() / 100, 2);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param Builder<self> $query */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('account_type', $type);
    }

    /** @param Builder<self> $query */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }
}
