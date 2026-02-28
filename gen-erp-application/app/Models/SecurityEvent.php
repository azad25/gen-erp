<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable security event record for tracking authentication and access anomalies.
 */
class SecurityEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'user_id',
        'event_type',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    // ── Event Types ──────────────────────────────────────────

    public const TYPE_FAILED_2FA = 'failed_2fa';
    public const TYPE_ACCOUNT_LOCKED = 'account_locked';
    public const TYPE_MASS_EXPORT = 'mass_export';
    public const TYPE_SUSPICIOUS_LOGIN = 'suspicious_login';
    public const TYPE_2FA_ENABLED = '2fa_enabled';
    public const TYPE_2FA_DISABLED = '2fa_disabled';
    public const TYPE_PASSWORD_RESET = 'password_reset';
    public const TYPE_ROLE_CHANGED = 'role_changed';

    // ── Relationships ────────────────────────────────────────

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
