<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentRequestStatus;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A manual payment request for subscription upgrade/renewal.
 */
class PaymentRequest extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'plan_id',
        'billing_cycle',
        'amount',
        'method',
        'transaction_id',
        'screenshot_path',
        'status',
        'admin_note',
        'verified_by',
        'verified_at',
        'submitted_by',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'method' => PaymentMethod::class,
            'status' => PaymentRequestStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** Formatted amount in BDT. */
    public function formattedAmount(): string
    {
        return '৳' . number_format($this->amount / 100, 2);
    }
}
