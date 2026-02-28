<?php

namespace App\Services\Integration\Drivers;

use App\Models\CompanyIntegration;
use App\Services\Integration\BaseNativeIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SMS Gateway integration for Bangladesh.
 * Supports SSL Wireless (BD primary), bDBL, and Twilio (international).
 */
class SmsGatewayIntegration extends BaseNativeIntegration
{
    public function slug(): string
    {
        return 'sms-gateway';
    }

    public function install(CompanyIntegration $ci): void
    {
        $this->registerHook($ci, 'order.confirmed', self::class . '@onOrderConfirmed');
    }

    public function uninstall(CompanyIntegration $ci): void
    {
        $ci->hooks()->delete();
    }

    /**
     * Send an SMS message.
     */
    public function send(CompanyIntegration $ci, string $phoneNumber, string $message): bool
    {
        $config = $ci->config ?? [];
        $provider = $config['provider'] ?? 'ssl_wireless';

        return match ($provider) {
            'ssl_wireless' => $this->sendViaSslWireless($config, $phoneNumber, $message),
            'twilio' => $this->sendViaTwilio($config, $phoneNumber, $message),
            default => false,
        };
    }

    private function sendViaSslWireless(array $config, string $phone, string $message): bool
    {
        try {
            $response = Http::post('https://smsplus.sslwireless.com/api/v3/send-sms', [
                'api_token' => $config['api_token'] ?? '',
                'sid' => $config['sender_id'] ?? '',
                'msisdn' => $phone,
                'sms' => $message,
                'csms_id' => uniqid('erp_'),
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('SMS SSL: Send failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function sendViaTwilio(array $config, string $phone, string $message): bool
    {
        try {
            $response = Http::withBasicAuth($config['account_sid'] ?? '', $config['auth_token'] ?? '')
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$config['account_sid']}/Messages.json", [
                    'To' => $phone,
                    'From' => $config['from_number'] ?? '',
                    'Body' => $message,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('SMS Twilio: Send failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function onOrderConfirmed(array $payload): void
    {
        Log::info('SMS: Order confirmed hook triggered', ['order' => $payload['order_number'] ?? '']);
    }
}
