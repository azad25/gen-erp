<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a pending team invitation for a company.
 */
class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'email',
        'role',
        'invited_by',
        'token',
        'accepted_at',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // ── Scopes ───────────────────────────────────────────────

    /**
     * Scope to only pending (not accepted and not expired) invitations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Invitation>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Invitation>
     */
    public function scopePending(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('accepted_at')
            ->where('expires_at', '>', now());
    }

    // ── Methods ──────────────────────────────────────────────

    /**
     * Check if this invitation has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
