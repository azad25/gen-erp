<?php

namespace App\Services\Integration\Drivers;

use App\Models\CompanyIntegration;
use App\Services\Integration\BaseNativeIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WooCommerce REST API integration.
 * Product/order sync for WordPress-based e-commerce stores.
 */
class WooCommerceIntegration extends BaseNativeIntegration
{
    public function slug(): string
    {
        return 'woocommerce';
    }

    public function install(CompanyIntegration $ci): void
    {
        $this->registerHook($ci, 'product.created', self::class . '@onProductCreated');
        $this->registerHook($ci, 'product.updated', self::class . '@onProductUpdated');
        $this->createSyncSchedule($ci, 'orders', 'pull', 'every_15_minutes');
        $this->createSyncSchedule($ci, 'products', 'push', 'hourly');
    }

    public function uninstall(CompanyIntegration $ci): void
    {
        $ci->hooks()->delete();
        $ci->syncSchedules()->delete();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pullOrders(CompanyIntegration $ci, int $page = 1): array
    {
        $config = $ci->config ?? [];

        try {
            $response = Http::withBasicAuth($config['consumer_key'] ?? '', $config['consumer_secret'] ?? '')
                ->get(rtrim($config['store_url'] ?? '', '/') . '/wp-json/wc/v3/orders', [
                    'page' => $page,
                    'per_page' => 50,
                    'orderby' => 'date',
                    'order' => 'desc',
                ]);

            return $response->successful() ? $response->json() : [];
        } catch (\Throwable $e) {
            Log::error('WooCommerce: Pull orders failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function onProductCreated(array $payload): void
    {
        Log::info('WooCommerce: Product sync triggered', ['product' => $payload['name'] ?? '']);
    }

    public function onProductUpdated(array $payload): void
    {
        Log::info('WooCommerce: Product update sync', ['product' => $payload['name'] ?? '']);
    }
}
