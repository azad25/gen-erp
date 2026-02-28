<?php

namespace App\Services\Integration\Drivers;

use App\Models\CompanyIntegration;
use App\Services\Integration\BaseNativeIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shopify e-commerce integration.
 * OAuth-based, handles products push, orders pull, and inventory sync.
 */
class ShopifyIntegration extends BaseNativeIntegration
{
    public function slug(): string
    {
        return 'shopify';
    }

    public function install(CompanyIntegration $ci): void
    {
        $this->registerHook($ci, 'product.created', self::class . '@onProductCreated');
        $this->registerHook($ci, 'product.updated', self::class . '@onProductUpdated');
        $this->registerHook($ci, 'inventory.adjusted', self::class . '@onInventoryAdjusted');
        $this->createSyncSchedule($ci, 'orders', 'pull', 'every_15_minutes');
        $this->createSyncSchedule($ci, 'products', 'push', 'hourly');
        $this->createInboundWebhook($ci, 'orders', [
            'shopify_id' => 'external_id',
            'line_items' => 'items',
            'total_price' => 'total_amount',
        ]);
    }

    public function uninstall(CompanyIntegration $ci): void
    {
        $ci->hooks()->delete();
        $ci->syncSchedules()->delete();
    }

    /**
     * Build OAuth authorization URL.
     */
    public function authorizationUrl(string $shop, string $redirectUri): string
    {
        $clientId = config('services.shopify.client_id', '');
        $scopes = 'read_products,write_products,read_orders,read_inventory,write_inventory';

        return "https://{$shop}/admin/oauth/authorize?" . http_build_query([
            'client_id' => $clientId,
            'scope' => $scopes,
            'redirect_uri' => $redirectUri,
        ]);
    }

    /**
     * Push a product to Shopify.
     *
     * @param array<string, mixed> $productData
     */
    public function pushProduct(CompanyIntegration $ci, array $productData): ?array
    {
        $config = $ci->config ?? [];
        $shop = $config['shop'] ?? '';
        $token = $config['access_token'] ?? '';

        try {
            $response = Http::withHeaders(['X-Shopify-Access-Token' => $token])
                ->post("https://{$shop}/admin/api/2024-01/products.json", [
                    'product' => $productData,
                ]);

            return $response->successful() ? $response->json('product') : null;
        } catch (\Throwable $e) {
            Log::error('Shopify: Push product failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Pull recent orders from Shopify.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pullOrders(CompanyIntegration $ci, ?string $sinceId = null): array
    {
        $config = $ci->config ?? [];
        $shop = $config['shop'] ?? '';
        $token = $config['access_token'] ?? '';

        try {
            $params = ['status' => 'any', 'limit' => 50];
            if ($sinceId) {
                $params['since_id'] = $sinceId;
            }

            $response = Http::withHeaders(['X-Shopify-Access-Token' => $token])
                ->get("https://{$shop}/admin/api/2024-01/orders.json", $params);

            return $response->successful() ? ($response->json('orders') ?? []) : [];
        } catch (\Throwable $e) {
            Log::error('Shopify: Pull orders failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function onProductCreated(array $payload): void
    {
        Log::info('Shopify: Product created, pushing to store', ['product' => $payload['name'] ?? '']);
    }

    public function onProductUpdated(array $payload): void
    {
        Log::info('Shopify: Product updated, syncing to store', ['product' => $payload['name'] ?? '']);
    }

    public function onInventoryAdjusted(array $payload): void
    {
        Log::info('Shopify: Inventory adjusted, syncing', ['product_id' => $payload['product_id'] ?? '']);
    }
}
