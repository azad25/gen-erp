<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A company's subscription to a plan.
 */
class Subscription extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'plan_id',
        'status',
        'billing_cycle',
        'starts_at',
        'ends_at',
        'grace_ends_at',
        'cancelled_at',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'grace_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return HasMany<SubscriptionInvoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    /** Whether access should be granted. */
    public function isAccessible(): bool
    {
        return $this->status->isAccessible();
    }

    /** Whether the subscription period has expired. */
    public function isPeriodExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    /** Whether the grace period has expired. */
    public function isGraceExpired(): bool
    {
        return $this->grace_ends_at && $this->grace_ends_at->isPast();
    }

    /** Days remaining in current period (or grace). */
    public function daysRemaining(): int
    {
        if ($this->status === SubscriptionStatus::GRACE && $this->grace_ends_at) {
            return max(0, (int) now()->diffInDays($this->grace_ends_at, false));
        }

        if ($this->ends_at) {
            return max(0, (int) now()->diffInDays($this->ends_at, false));
        }

        return 0;
    }
}
