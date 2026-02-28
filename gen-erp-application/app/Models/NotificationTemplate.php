<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-event, per-channel notification template.
 */
class NotificationTemplate extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'event_key',
        'channel',
        'locale',
        'subject',
        'body',
        'is_active',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }
}
