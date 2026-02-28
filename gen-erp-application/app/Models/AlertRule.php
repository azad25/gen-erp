<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * A configurable alert rule that triggers notifications when conditions are met.
 */
class AlertRule extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'entity_type',
        'trigger_field',
        'operator',
        'trigger_value',
        'channels',
        'target_roles',
        'target_user_ids',
        'message_template',
        'repeat_behaviour',
        'cooldown_minutes',
        'is_active',
        'last_triggered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'target_roles' => 'array',
            'target_user_ids' => 'array',
            'is_active' => 'boolean',
            'cooldown_minutes' => 'integer',
            'last_triggered_at' => 'datetime',
        ];
    }
}
