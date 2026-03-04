<?php

namespace App\Domain\Accounting\Models;

use App\Support\Enums\JournalCode;
use App\Support\Enums\JournalEntryStatus;
use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;

/**
 * Double-entry journal entry header.
 */
class JournalEntry extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'idempotency_key',
        'entry_number',
        'journal_code',
        'entry_date',
        'reference_type',
        'reference_id',
        'description',
        'status',
        'posted_at',
        'currency',
        'is_system',
        'created_by',
        'posted_by',
        'reversed_by_id',
        'reversal_of_id',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'posted_at' => 'datetime',
            'is_system' => 'boolean',
            'status' => JournalEntryStatus::class,
            'journal_code' => JournalCode::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (JournalEntry $entry): void {
            if ($entry->entry_number === null || $entry->entry_number === '') {
                $seq = static::withoutGlobalScopes()
                    ->where('company_id', $entry->company_id)
                    ->count() + 1;
                $entry->entry_number = 'JE-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }
        });

        static::updating(function (JournalEntry $entry): void {
            $original = $entry->getOriginal('status');
            $isPosted = $original === JournalEntryStatus::POSTED
                || $original === JournalEntryStatus::POSTED->value;

            if ($isPosted) {
                throw new RuntimeException(__('Posted journal entries cannot be modified.'));
            }
        });
    }

    /** @return MorphTo<Model, $this> */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /** @return HasMany<JournalEntryLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * The entry that reversed this one (if any).
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_by_id');
    }

    /**
     * The original entry this is a reversal of (if any).
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function totalDebits(): int
    {
        return (int) $this->lines()->sum('debit');
    }

    public function totalCredits(): int
    {
        return (int) $this->lines()->sum('credit');
    }

    public function isBalanced(): bool
    {
        return $this->totalDebits() === $this->totalCredits();
    }
}
