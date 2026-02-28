<?php

namespace App\Models;

use App\Enums\JournalEntryStatus;
use App\Models\Traits\BelongsToCompany;
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
        'entry_number',
        'entry_date',
        'reference_type',
        'reference_id',
        'description',
        'status',
        'is_system',
        'created_by',
        'posted_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'is_system' => 'boolean',
            'status' => JournalEntryStatus::class,
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
