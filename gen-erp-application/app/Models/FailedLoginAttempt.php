<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Records failed login attempts for security monitoring and account lockout.
 */
class FailedLoginAttempt extends Model
{
    /**
     * Only uses `created_at`, no `updated_at`.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'ip_address',
        'user_agent',
        'target_email',
    ];
}
