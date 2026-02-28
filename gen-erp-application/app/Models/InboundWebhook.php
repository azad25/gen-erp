<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Inbound webhook endpoint — company-specific URL for external systems to POST data. */
class InboundWebhook extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'company_integration_id',
        'endpoint_key',
        'secret',
        'entity_type',
        'field_maps',
        'is_active',
        'last_received_at',
        'received_count',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'field_maps' => 'array',
            'is_active' => 'boolean',
            'last_received_at' => 'datetime',
            'received_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $webhook): void {
            if (empty($webhook->endpoint_key)) {
                $webhook->endpoint_key = Str::random(48);
            }
            if (empty($webhook->secret)) {
                $webhook->secret = Str::random(64);
            }
        });
    }

    public function companyIntegration(): BelongsTo
    {
        return $this->belongsTo(CompanyIntegration::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Get the full inbound webhook URL. */
    public function getUrlAttribute(): string
    {
        return url("/hooks/in/{$this->endpoint_key}");
    }

    /** Verify an HMAC signature against the webhook secret. */
    public function verifySignature(string $payload, string $signature): bool
    {
        $expected = hash_hmac('sha256', $payload, $this->secret);

        return hash_equals($expected, $signature);
    }

    /** Increment the received counter and update timestamp. */
    public function recordReceived(): void
    {
        $this->increment('received_count');
        $this->update(['last_received_at' => now()]);
    }
}
