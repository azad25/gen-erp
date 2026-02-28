<?php

namespace Database\Seeders;

use App\Models\Integration;
use Illuminate\Database\Seeder;

/**
 * Seeds all native (Tier 1) integrations into the integrations table.
 * All require at least a 'pro' plan unless noted.
 */
class IntegrationSeeder extends Seeder
{
    public function run(): void
    {
        $integrations = [
            // ── Phase 11A: IoT & Hardware ──
            [
                'name' => 'ZKTeco Biometric',
                'slug' => 'zkteco-biometric',
                'author' => 'ZKTeco',
                'tier' => 'native',
                'category' => 'iot_hardware',
                'description' => 'Biometric attendance device integration (F22, K40, UA300E). Auto-syncs punch data to employee attendance.',
                'config_schema' => ['ip' => 'string', 'port' => 'integer', 'model' => 'string'],
                'min_plan' => 'pro',
                'is_active' => true,
                'capabilities' => [],
            ],
            [
                'name' => 'Thermal Printer',
                'slug' => 'thermal-printer',
                'author' => 'Generic',
                'tier' => 'native',
                'category' => 'iot_hardware',
                'description' => 'ESC/POS thermal receipt printer. Supports 80mm/58mm via network TCP.',
                'config_schema' => ['ip' => 'string', 'port' => 'integer', 'width' => 'string'],
                'min_plan' => 'free',
                'is_active' => true,
                'capabilities' => [],
            ],

            // ── Phase 11B: Email & Communication ──
            [
                'name' => 'Custom SMTP',
                'slug' => 'custom-smtp',
                'author' => 'GenERP',
                'tier' => 'native',
                'category' => 'communication',
                'description' => 'Send notifications from your own domain via custom SMTP settings.',
                'config_schema' => ['smtp_host' => 'string', 'smtp_port' => 'integer', 'smtp_username' => 'string', 'smtp_password' => 'string', 'smtp_encryption' => 'string', 'from_address' => 'string', 'from_name' => 'string'],
                'min_plan' => 'pro',
                'is_active' => true,
                'capabilities' => [],
            ],
            [
                'name' => 'WhatsApp Business',
                'slug' => 'whatsapp-business',
                'author' => 'WATI / MessageBird',
                'tier' => 'native',
                'category' => 'communication',
                'description' => 'Send invoice links and payment reminders via WhatsApp Business API.',
                'config_schema' => ['wati_provider' => 'string', 'api_key' => 'string', 'wati_url' => 'string'],
                'min_plan' => 'pro',
                'is_active' => true,
                'capabilities' => [],
            ],
            [
                'name' => 'SMS Gateway',
                'slug' => 'sms-gateway',
                'author' => 'SSL Wireless / Twilio',
                'tier' => 'native',
                'category' => 'communication',
                'description' => 'Send SMS notifications (order confirmations, OTP) via SSL Wireless (BD) or Twilio.',
                'config_schema' => ['sms_provider' => 'string', 'api_token' => 'string', 'sender_id' => 'string'],
                'min_plan' => 'pro',
                'is_active' => true,
                'capabilities' => [],
            ],

            // ── Phase 11C: Google Workspace ──
            [
                'name' => 'Google Drive',
                'slug' => 'google-drive',
                'author' => 'Google',
                'tier' => 'native',
                'category' => 'google',
                'description' => 'Auto-export invoices, payslips, and Mushak reports to Google Drive.',
                'config_schema' => ['access_token' => 'string', 'refresh_token' => 'string', 'folder_id' => 'string'],
                'min_plan' => 'pro',
                'is_active' => true,
                'capabilities' => [],
            ],

            // ── Phase 12A: E-commerce ──
            [
                'name' => 'Shopify',
                'slug' => 'shopify',
                'author' => 'Shopify',
                'tier' => 'native',
                'category' => 'ecommerce',
                'description' => 'Sync products, orders, and inventory with your Shopify store.',
                'config_schema' => ['shop' => 'string', 'access_token' => 'string'],
                'min_plan' => 'pro',
                'is_active' => true,
                'capabilities' => [],
            ],
            [
                'name' => 'WooCommerce',
                'slug' => 'woocommerce',
                'author' => 'WooCommerce',
                'tier' => 'native',
                'category' => 'ecommerce',
                'description' => 'Sync products and orders with your WordPress/WooCommerce store.',
                'config_schema' => ['store_url' => 'string', 'consumer_key' => 'string', 'consumer_secret' => 'string'],
                'min_plan' => 'pro',
                'is_active' => true,
                'capabilities' => [],
            ],
            [
                'name' => 'Daraz',
                'slug' => 'daraz',
                'author' => 'Daraz / Lazada',
                'tier' => 'native',
                'category' => 'ecommerce',
                'description' => 'Sync products and pull orders from Daraz Bangladesh marketplace.',
                'config_schema' => ['app_key' => 'string', 'access_token' => 'string'],
                'min_plan' => 'pro',
                'is_active' => true,
                'capabilities' => [],
            ],
        ];

        foreach ($integrations as $data) {
            Integration::updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
        }
    }
}
