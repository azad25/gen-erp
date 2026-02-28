<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * POS cash register session for a branch.
 */
class POSSession extends Model
{
    use BelongsToCompany;

    protected $table = 'pos_sessions';

    protected $fillable = [
        'company_id',
        'branch_id',
        'opened_by',
        'closed_by',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'cash_difference',
        'status',
        'opened_at',
        'closed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_cash' => 'integer',
            'closing_cash' => 'integer',
            'expected_cash' => 'integer',
            'cash_difference' => 'integer',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<User, $this> */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /** @return BelongsTo<User, $this> */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /** @return HasMany<POSSale, $this> */
    public function sales(): HasMany
    {
        return $this->hasMany(POSSale::class, 'pos_session_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
