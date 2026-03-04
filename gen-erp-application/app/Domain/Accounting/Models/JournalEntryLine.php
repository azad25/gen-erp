<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single line in a journal entry — either a debit or credit to one account.
 */
class JournalEntryLine extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'journal_entry_id',
        'account_id',
        'line_no',
        'description',
        'debit',
        'credit',
        'tax_code',
        'tax_rate',
        'tax_base_amount',
        'branch_id',
        'cost_center_id',
        'dimensions',
    ];

    protected function casts(): array
    {
        return [
            'line_no' => 'integer',
            'debit' => 'integer',
            'credit' => 'integer',
            'tax_rate' => 'integer',
            'tax_base_amount' => 'integer',
            'dimensions' => 'array',
        ];
    }

    /** @return BelongsTo<JournalEntry, $this> */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
