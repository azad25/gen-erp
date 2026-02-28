<?php

namespace App\Services\Integration;

use App\Models\OutboundWebhook;
use App\Models\OutboundWebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Dispatches outbound webhooks with HMAC-SHA256 signatures and 3x retries.
 */
class OutboundWebhookService
{
    /**
     * Fire an event to all registered webhooks for a company.
     *
     * @param array<string, mixed> $payload
     */
    public function dispatch(int $companyId, string $event, array $payload): int
    {
        $webhooks = OutboundWebhook::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('event_name', $event)
            ->where('is_active', true)
            ->get();

        $sent = 0;
        foreach ($webhooks as $webhook) {
            if ($this->send($webhook, $payload)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Send a webhook with HMAC signing and retries.
     */
    public function send(OutboundWebhook $webhook, array $payload): bool
    {
        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = $this->sign($jsonPayload, $webhook->secret);

        for ($attempt = 1; $attempt <= $webhook->max_retries; $attempt++) {
            $start = microtime(true);

            try {
                $response = Http::timeout($webhook->timeout_seconds)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'X-Webhook-Signature' => $signature,
                        'X-Webhook-Event' => $webhook->event_name,
                        'X-Webhook-Timestamp' => (string) time(),
                    ])
                    ->withBody($jsonPayload, 'application/json')
                    ->post($webhook->url);

                $durationMs = (int) ((microtime(true) - $start) * 1000);

                OutboundWebhookLog::create([
                    'outbound_webhook_id' => $webhook->id,
                    'attempt' => $attempt,
                    'http_status' => $response->status(),
                    'response_body' => mb_substr($response->body(), 0, 2000),
                    'duration_ms' => $durationMs,
                ]);

                if ($response->successful()) {
                    $webhook->update([
                        'last_triggered_at' => now(),
                        'failure_count' => 0,
                    ]);

                    return true;
                }
            } catch (\Throwable $e) {
                $durationMs = (int) ((microtime(true) - $start) * 1000);

                OutboundWebhookLog::create([
                    'outbound_webhook_id' => $webhook->id,
                    'attempt' => $attempt,
                    'error_message' => mb_substr($e->getMessage(), 0, 1000),
                    'duration_ms' => $durationMs,
                ]);
            }

            // Exponential backoff between retries
            if ($attempt < $webhook->max_retries) {
                usleep($attempt * 500_000); // 0.5s, 1s, 1.5s
            }
        }

        // All retries exhausted
        $webhook->increment('failure_count');

        // Auto-disable after 10 consecutive failures
        if ($webhook->failure_count >= 10) {
            $webhook->update(['is_active' => false]);
            Log::warning('Outbound webhook auto-disabled after 10 failures', [
                'webhook_id' => $webhook->id,
                'url' => $webhook->url,
            ]);
        }

        return false;
    }

    /**
     * Generate HMAC-SHA256 signature for webhook payload.
     */
    public function sign(string $payload, string $secret): string
    {
        return 'sha256=' . hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify a received webhook signature.
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expected = $this->sign($payload, $secret);
        return hash_equals($expected, $signature);
    }
}
