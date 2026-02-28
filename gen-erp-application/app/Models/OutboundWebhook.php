<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A company's outbound webhook subscription for a particular event.
 */
class OutboundWebhook extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'event_name',
        'url',
        'secret',
        'is_active',
        'max_retries',
        'timeout_seconds',
        'last_triggered_at',
        'failure_count',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'max_retries' => 'integer',
            'timeout_seconds' => 'integer',
            'failure_count' => 'integer',
            'last_triggered_at' => 'datetime',
        ];
    }

    /** @return HasMany<OutboundWebhookLog, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(OutboundWebhookLog::class);
    }
}
