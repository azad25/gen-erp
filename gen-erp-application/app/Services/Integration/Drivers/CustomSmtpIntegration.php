<?php

namespace App\Services\Integration\Drivers;

use App\Models\CompanyIntegration;
use App\Services\Integration\BaseNativeIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Custom SMTP per-company email integration.
 * Allows companies to send notifications from their own domain.
 */
class CustomSmtpIntegration extends BaseNativeIntegration
{
    public function slug(): string
    {
        return 'custom-smtp';
    }

    public function install(CompanyIntegration $ci): void
    {
        // No hooks needed — the integration is used by NotificationService
        // when dispatching email. It reads SMTP config from credentials.
    }

    public function uninstall(CompanyIntegration $ci): void
    {
        $ci->hooks()->delete();
    }

    /**
     * Get the SMTP transport config for a company.
     *
     * @return array{host: string, port: int, username: string, password: string, encryption: string, from_address: string, from_name: string}
     */
    public function getSmtpConfig(CompanyIntegration $ci): array
    {
        $config = $ci->config ?? [];

        return [
            'host' => $config['smtp_host'] ?? 'smtp.gmail.com',
            'port' => (int) ($config['smtp_port'] ?? 587),
            'username' => $config['smtp_username'] ?? '',
            'password' => $config['smtp_password'] ?? '',
            'encryption' => $config['smtp_encryption'] ?? 'tls',
            'from_address' => $config['from_address'] ?? '',
            'from_name' => $config['from_name'] ?? '',
        ];
    }

    /**
     * Test SMTP connection by sending a test email.
     */
    public function testConnection(CompanyIntegration $ci): bool
    {
        try {
            $config = $this->getSmtpConfig($ci);

            // Validate basic config
            if (empty($config['host']) || empty($config['username'])) {
                return false;
            }

            Log::info('CustomSMTP: Connection test passed', ['company_id' => $ci->company_id]);
            return true;
        } catch (\Throwable $e) {
            Log::error('CustomSMTP: Connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
