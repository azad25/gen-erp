<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable log entry for a triggered alert.
 */
class AlertLog extends Model
{
    use BelongsToCompany;

    public const UPDATED_AT = null;

    protected $table = 'alert_log';

    protected $fillable = [
        'company_id',
        'alert_rule_id',
        'entity_type',
        'entity_id',
        'triggered_value',
        'channels_sent',
        'recipients_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'channels_sent' => 'array',
            'recipients_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AlertRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class, 'alert_rule_id');
    }
}
