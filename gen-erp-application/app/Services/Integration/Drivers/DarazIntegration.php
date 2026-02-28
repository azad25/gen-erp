<?php

namespace App\Services\Integration\Drivers;

use App\Models\CompanyIntegration;
use App\Services\Integration\BaseNativeIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Daraz Seller API integration (Bangladesh's largest marketplace).
 * Product/order sync via Lazada Open Platform API.
 */
class DarazIntegration extends BaseNativeIntegration
{
    public function slug(): string
    {
        return 'daraz';
    }

    public function install(CompanyIntegration $ci): void
    {
        $this->registerHook($ci, 'product.created', self::class . '@onProductCreated');
        $this->createSyncSchedule($ci, 'orders', 'pull', 'every_15_minutes');
    }

    public function uninstall(CompanyIntegration $ci): void
    {
        $ci->hooks()->delete();
        $ci->syncSchedules()->delete();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pullOrders(CompanyIntegration $ci): array
    {
        $config = $ci->config ?? [];
        $appKey = $config['app_key'] ?? '';
        $accessToken = $config['access_token'] ?? '';

        try {
            $response = Http::get('https://api.daraz.com.bd/rest', [
                'app_key' => $appKey,
                'access_token' => $accessToken,
                'method' => '/orders/get',
                'format' => 'JSON',
                'sign_method' => 'sha256',
                'timestamp' => now()->timestamp * 1000,
            ]);

            return $response->successful() ? ($response->json('data.orders') ?? []) : [];
        } catch (\Throwable $e) {
            Log::error('Daraz: Pull orders failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function onProductCreated(array $payload): void
    {
        Log::info('Daraz: Product created, pushing to marketplace', ['product' => $payload['name'] ?? '']);
    }
}
