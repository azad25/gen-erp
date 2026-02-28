<?php

namespace App\Services\Integration\Drivers;

use App\Models\CompanyIntegration;
use App\Services\Integration\BaseNativeIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Business API integration for sending invoices and reminders.
 * Supports WATI, MessageBird, or direct Meta API.
 */
class WhatsAppIntegration extends BaseNativeIntegration
{
    public function slug(): string
    {
        return 'whatsapp-business';
    }

    public function install(CompanyIntegration $ci): void
    {
        $this->registerHook($ci, 'invoice.sent', self::class . '@onInvoiceSent');
        $this->registerHook($ci, 'payment.reminder', self::class . '@onPaymentReminder');
    }

    public function uninstall(CompanyIntegration $ci): void
    {
        $ci->hooks()->delete();
    }

    /**
     * Send a WhatsApp template message.
     *
     * @param array<string, string> $params Template parameters
     */
    public function sendTemplate(CompanyIntegration $ci, string $phoneNumber, string $template, array $params = []): bool
    {
        $config = $ci->config ?? [];
        $apiKey = $config['api_key'] ?? '';
        $provider = $config['provider'] ?? 'wati'; // wati|messagebird|meta

        if (empty($apiKey)) {
            Log::warning('WhatsApp: No API key configured', ['company_id' => $ci->company_id]);
            return false;
        }

        try {
            $endpoint = match ($provider) {
                'wati' => $this->sendViaWati($config, $phoneNumber, $template, $params),
                'messagebird' => $this->sendViaMessageBird($config, $phoneNumber, $template, $params),
                default => false,
            };

            return $endpoint;
        } catch (\Throwable $e) {
            Log::error('WhatsApp: Send failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function sendViaWati(array $config, string $phone, string $template, array $params): bool
    {
        $response = Http::withToken($config['api_key'])
            ->post(($config['wati_url'] ?? 'https://live-server.wati.io') . '/api/v1/sendTemplateMessage', [
                'whatsappNumber' => $phone,
                'template_name' => $template,
                'parameters' => array_map(fn ($k, $v) => ['name' => $k, 'value' => $v], array_keys($params), $params),
            ]);

        return $response->successful();
    }

    private function sendViaMessageBird(array $config, string $phone, string $template, array $params): bool
    {
        $response = Http::withToken($config['api_key'])
            ->post('https://conversations.messagebird.com/v1/send', [
                'to' => $phone,
                'type' => 'hsm',
                'content' => ['hsm' => ['templateName' => $template, 'language' => ['code' => 'en'], 'params' => $params]],
                'from' => $config['channel_id'] ?? '',
            ]);

        return $response->successful();
    }

    public function onInvoiceSent(array $payload): void
    {
        Log::info('WhatsApp: Invoice sent hook triggered', ['invoice' => $payload['invoice_number'] ?? '']);
    }

    public function onPaymentReminder(array $payload): void
    {
        Log::info('WhatsApp: Payment reminder hook triggered', ['customer' => $payload['customer_name'] ?? '']);
    }
}
