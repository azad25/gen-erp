<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log entry for an outbound webhook delivery attempt.
 */
class OutboundWebhookLog extends Model
{
    protected $fillable = [
        'outbound_webhook_id',
        'attempt',
        'http_status',
        'response_body',
        'error_message',
        'duration_ms',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'attempt' => 'integer',
            'http_status' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    /** @return BelongsTo<OutboundWebhook, $this> */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(OutboundWebhook::class, 'outbound_webhook_id');
    }
}
