<?php

use App\Models\Company;
use App\Models\Integration;
use App\Models\OutboundWebhook;
use App\Models\OutboundWebhookLog;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\Integration\Drivers\CustomSmtpIntegration;
use App\Services\Integration\Drivers\DarazIntegration;
use App\Services\Integration\Drivers\ShopifyIntegration;
use App\Services\Integration\Drivers\SmsGatewayIntegration;
use App\Services\Integration\Drivers\ThermalPrinterDriver;
use App\Services\Integration\Drivers\WhatsAppIntegration;
use App\Services\Integration\Drivers\WooCommerceIntegration;
use App\Services\Integration\Drivers\ZKTecoDriver;
use App\Services\Integration\OutboundWebhookService;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    CompanyContext::setActive($this->company);
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company->id, ['role' => 'owner']);

    // Seed integrations
    $this->seed(\Database\Seeders\IntegrationSeeder::class);
});

// ── Driver Instantiation ────────────────────────────────────────

test('all native drivers return correct slugs', function (): void {
    expect((new ZKTecoDriver())->slug())->toBe('zkteco-biometric');
    expect((new ThermalPrinterDriver())->slug())->toBe('thermal-printer');
    expect((new CustomSmtpIntegration())->slug())->toBe('custom-smtp');
    expect((new WhatsAppIntegration())->slug())->toBe('whatsapp-business');
    expect((new SmsGatewayIntegration())->slug())->toBe('sms-gateway');
    expect((new ShopifyIntegration())->slug())->toBe('shopify');
    expect((new WooCommerceIntegration())->slug())->toBe('woocommerce');
    expect((new DarazIntegration())->slug())->toBe('daraz');
});

test('integration seeder registers all 9 native integrations', function (): void {
    expect(Integration::count())->toBe(9);

    $slugs = Integration::pluck('slug')->toArray();
    expect($slugs)->toContain('zkteco-biometric');
    expect($slugs)->toContain('shopify');
    expect($slugs)->toContain('daraz');
    expect($slugs)->toContain('whatsapp-business');
});

test('integration categories are assigned correctly', function (): void {
    $iot = Integration::where('category', 'iot_hardware')->count();
    $communication = Integration::where('category', 'communication')->count();
    $ecommerce = Integration::where('category', 'ecommerce')->count();
    $google = Integration::where('category', 'google')->count();

    expect($iot)->toBe(2);
    expect($communication)->toBe(3);
    expect($ecommerce)->toBe(3);
    expect($google)->toBe(1);
});

test('integrations have plan restrictions', function (): void {
    $freeIntegrations = Integration::where('min_plan', 'free')->pluck('slug');
    $proIntegrations = Integration::where('min_plan', 'pro')->pluck('slug');

    // Thermal printer is free tier
    expect($freeIntegrations)->toContain('thermal-printer');
    expect($freeIntegrations)->toHaveCount(1);

    // Everything else requires pro
    expect($proIntegrations)->toHaveCount(8);
});

// ── Outbound Webhook Service ────────────────────────────────────

test('HMAC signature generation and verification', function (): void {
    $service = new OutboundWebhookService();

    $payload = '{"event":"order.created","data":{"id":1}}';
    $secret = 'my-webhook-secret';

    $signature = $service->sign($payload, $secret);
    expect($signature)->toStartWith('sha256=');

    // Verify succeeds with correct secret
    expect($service->verifySignature($payload, $signature, $secret))->toBeTrue();

    // Verify fails with wrong secret
    expect($service->verifySignature($payload, $signature, 'wrong-secret'))->toBeFalse();

    // Verify fails with tampered payload
    expect($service->verifySignature($payload . 'tampered', $signature, $secret))->toBeFalse();
});

test('outbound webhook dispatches to registered URLs', function (): void {
    Http::fake([
        'https://example.com/webhook' => Http::response(['ok' => true], 200),
    ]);

    $webhook = OutboundWebhook::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'event_name' => 'order.created',
        'url' => 'https://example.com/webhook',
        'secret' => 'test-secret-key',
        'is_active' => true,
        'max_retries' => 1,
    ]);

    $service = new OutboundWebhookService();
    $sent = $service->dispatch($this->company->id, 'order.created', ['order_id' => 42]);

    expect($sent)->toBe(1);

    // Delivery log created
    expect(OutboundWebhookLog::where('outbound_webhook_id', $webhook->id)->count())->toBe(1);
    $log = OutboundWebhookLog::where('outbound_webhook_id', $webhook->id)->first();
    expect($log->http_status)->toBe(200);

    // Webhook updated
    $webhook->refresh();
    expect($webhook->last_triggered_at)->not->toBeNull();
    expect($webhook->failure_count)->toBe(0);
});

test('outbound webhook ignores inactive webhooks', function (): void {
    OutboundWebhook::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'event_name' => 'order.created',
        'url' => 'https://example.com/webhook',
        'secret' => 'test-key',
        'is_active' => false,
    ]);

    $service = new OutboundWebhookService();
    $sent = $service->dispatch($this->company->id, 'order.created', ['test' => true]);
    expect($sent)->toBe(0);
});

test('outbound webhook increments failure count on failure', function (): void {
    Http::fake([
        'https://example.com/fail' => Http::response('Error', 500),
    ]);

    $webhook = OutboundWebhook::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'event_name' => 'test.event',
        'url' => 'https://example.com/fail',
        'secret' => 'test-key',
        'is_active' => true,
        'max_retries' => 1,
    ]);

    $service = new OutboundWebhookService();
    $result = $service->send($webhook, ['test' => true]);

    expect($result)->toBeFalse();
    $webhook->refresh();
    expect($webhook->failure_count)->toBe(1);
});

// ── Tenant Isolation ────────────────────────────────────────────

test('outbound webhooks are isolated between companies', function (): void {
    $companyB = Company::factory()->create();

    OutboundWebhook::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'event_name' => 'order.created',
        'url' => 'https://a.com/hook',
        'secret' => 'a',
    ]);

    OutboundWebhook::withoutGlobalScopes()->create([
        'company_id' => $companyB->id,
        'event_name' => 'order.created',
        'url' => 'https://b.com/hook',
        'secret' => 'b',
    ]);

    CompanyContext::setActive($this->company);
    expect(OutboundWebhook::count())->toBe(1);

    CompanyContext::setActive($companyB);
    expect(OutboundWebhook::count())->toBe(1);
});
