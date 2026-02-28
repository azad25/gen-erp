<?php

use App\Models\Company;
use App\Models\CompanyIntegration;
use App\Models\InboundWebhook;
use App\Models\Integration;
use App\Models\IntegrationCredential;
use App\Models\IntegrationHook;
use App\Models\IntegrationLog;
use App\Models\IoTDevice;
use App\Models\SyncSchedule;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\Integration\CredentialVault;
use App\Services\Integration\FieldMapper;
use App\Services\Integration\HookDispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    CompanyContext::setActive($this->company);
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company->id, ['role' => 'owner']);

    $this->integration = Integration::create([
        'slug' => 'test-integration',
        'name' => 'Test Integration',
        'category' => 'custom',
        'tier' => 'native',
        'min_plan' => 'pro',
        'config_schema' => [],
        'capabilities' => ['push', 'pull'],
    ]);

    $this->companyIntegration = CompanyIntegration::create([
        'company_id' => $this->company->id,
        'integration_id' => $this->integration->id,
        'config' => [],
        'field_maps' => [],
        'status' => 'active',
        'installed_at' => now(),
    ]);
});

// ── HookDispatcher ──────────────────────────────────────────────

test('hook dispatcher dispatches handlers to queue', function (): void {
    Queue::fake();

    IntegrationHook::create([
        'company_id' => $this->company->id,
        'company_integration_id' => $this->companyIntegration->id,
        'hook_name' => 'invoice.created',
        'handler_class' => 'App\\Tests\\Stubs\\TestHookHandler',
        'priority' => 10,
    ]);

    // Clear cache so it picks up new hook
    Cache::flush();

    HookDispatcher::action('invoice.created', ['invoice_id' => 1]);

    Queue::assertPushed(\App\Jobs\RunHookHandlerJob::class);
});

test('hook dispatcher does nothing without active company', function (): void {
    Queue::fake();
    // Remove active company to simulate no-company context
    app()->forgetInstance('active_company');
    app()->forgetInstance('active_company_id');

    HookDispatcher::action('invoice.created', ['test' => true]);

    Queue::assertNothingPushed();
});

test('hook dispatcher ignores inactive hooks', function (): void {
    Queue::fake();

    IntegrationHook::create([
        'company_id' => $this->company->id,
        'company_integration_id' => $this->companyIntegration->id,
        'hook_name' => 'invoice.created',
        'handler_class' => 'App\\Tests\\Stubs\\TestHookHandler',
        'is_active' => false,
    ]);

    Cache::flush();

    HookDispatcher::action('invoice.created', ['test' => true]);

    Queue::assertNothingPushed();
});

// ── CredentialVault ─────────────────────────────────────────────

test('credential vault stores and retrieves encrypted credentials', function (): void {
    $vault = app(CredentialVault::class);

    $vault->store($this->company->id, $this->companyIntegration->id, 'api_key', 'secret-123');

    $retrieved = $vault->retrieve($this->company->id, $this->companyIntegration->id, 'api_key');

    expect($retrieved)->toBe('secret-123');

    // Verify raw DB value is encrypted (not plain text)
    $raw = IntegrationCredential::first()->getRawOriginal('credential_value');
    expect($raw)->not->toBe('secret-123');
});

test('credential vault prevents cross-company credential access', function (): void {
    $vault = app(CredentialVault::class);
    $companyB = Company::factory()->create();
    $integrationB = CompanyIntegration::withoutGlobalScopes()->create([
        'company_id' => $companyB->id,
        'integration_id' => $this->integration->id,
        'config' => [],
        'field_maps' => [],
        'status' => 'active',
        'installed_at' => now(),
    ]);

    $vault->store($this->company->id, $this->companyIntegration->id, 'api_key', 'company-a-secret');

    // Company B trying to access Company A's credential should fail
    expect(fn () => $vault->retrieve($companyB->id, $integrationB->id, 'api_key'))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('credential vault stores and retrieves oauth tokens', function (): void {
    $vault = app(CredentialVault::class);

    $vault->storeOAuthTokens($this->company->id, $this->companyIntegration->id, [
        'access_token' => 'at-123',
        'refresh_token' => 'rt-456',
        'expires_at' => '2026-12-31 23:59:59',
    ]);

    $tokens = $vault->getOAuthTokens($this->company->id, $this->companyIntegration->id);

    expect($tokens['access_token'])->toBe('at-123');
    expect($tokens['refresh_token'])->toBe('rt-456');
    expect($tokens['expires_at'])->toBe('2026-12-31 23:59:59');
});

// ── FieldMapper ─────────────────────────────────────────────────

test('field mapper maps outbound data with transforms', function (): void {
    $mapper = new FieldMapper;

    $source = [
        'name' => 'Widget A',
        'total_amount' => 150000,
        'sku' => 'wdg-001',
    ];

    $maps = [
        ['generpbd_field' => 'name', 'external_field' => 'product_title', 'transform' => 'upper'],
        ['generpbd_field' => 'total_amount', 'external_field' => 'price', 'transform' => 'divide_by_100'],
        ['generpbd_field' => 'sku', 'external_field' => 'variant.sku', 'transform' => null],
    ];

    $result = $mapper->mapOutbound($source, $maps);

    expect($result['product_title'])->toBe('WIDGET A');
    expect($result['price'])->toBe(1500);
    expect($result['variant']['sku'])->toBe('wdg-001');
});

test('field mapper maps inbound data with reverse paise transform', function (): void {
    $mapper = new FieldMapper;

    $external = [
        'order' => [
            'total_price' => 1500.00,
            'billing_address' => ['name' => 'ABC Corp'],
        ],
    ];

    $maps = [
        ['generpbd_field' => 'total_amount', 'external_field' => 'order.total_price', 'transform' => 'divide_by_100'],
        ['generpbd_field' => 'customer_name', 'external_field' => 'order.billing_address.name', 'transform' => null],
    ];

    $result = $mapper->mapInbound($external, $maps);

    // Reverse of divide_by_100 is multiply_by_100 (BDT → paise)
    expect($result['total_amount'])->toBe(150000);
    expect($result['customer_name'])->toBe('ABC Corp');
});

// ── InboundWebhook ──────────────────────────────────────────────

test('inbound webhook auto-generates endpoint key and secret', function (): void {
    $webhook = InboundWebhook::create([
        'company_id' => $this->company->id,
        'company_integration_id' => $this->companyIntegration->id,
        'entity_type' => 'sales_order',
        'field_maps' => [],
    ]);

    expect($webhook->endpoint_key)->not->toBeEmpty();
    expect(strlen($webhook->endpoint_key))->toBe(48);
    expect($webhook->secret)->not->toBeEmpty();
    expect(strlen($webhook->secret))->toBe(64);
});

test('inbound webhook verifies HMAC signature correctly', function (): void {
    $webhook = InboundWebhook::create([
        'company_id' => $this->company->id,
        'company_integration_id' => $this->companyIntegration->id,
        'entity_type' => 'sales_order',
        'field_maps' => [],
    ]);

    $payload = '{"order_id": 123}';
    $validSignature = hash_hmac('sha256', $payload, $webhook->secret);

    expect($webhook->verifySignature($payload, $validSignature))->toBeTrue();
    expect($webhook->verifySignature($payload, 'invalid-signature'))->toBeFalse();
});

// ── IntegrationLog ──────────────────────────────────────────────

test('integration log records success and failure', function (): void {
    IntegrationLog::success($this->companyIntegration->id, 'invoice.created', 150, $this->company->id);
    IntegrationLog::failure($this->companyIntegration->id, 'invoice.created', 'Connection timeout', $this->company->id);

    $logs = IntegrationLog::all();
    expect($logs)->toHaveCount(2);
    expect($logs[0]->status)->toBe('success');
    expect($logs[0]->duration_ms)->toBe(150);
    expect($logs[1]->status)->toBe('failed');
    expect($logs[1]->error_message)->toBe('Connection timeout');
});

// ── SyncSchedule ────────────────────────────────────────────────

test('sync schedule calculates next run time correctly', function (): void {
    $schedule = SyncSchedule::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'company_integration_id' => $this->companyIntegration->id,
        'entity_type' => 'products',
        'direction' => 'push',
        'frequency' => 'hourly',
        'next_run_at' => now()->subHour(),
        'is_active' => true,
    ]);

    $fresh = SyncSchedule::withoutGlobalScopes()->find($schedule->id);
    expect($fresh->isDue())->toBeTrue();

    $nextRun = $schedule->calculateNextRunAt();
    $diffMinutes = (int) abs($nextRun->diffInMinutes(now()));
    expect($diffMinutes)->toBeLessThanOrEqual(61);
    expect($diffMinutes)->toBeGreaterThanOrEqual(59);
});

// ── Integration Model ───────────────────────────────────────────

test('integration plan eligibility check works', function (): void {
    expect($this->integration->isPlanEligible('enterprise'))->toBeTrue();
    expect($this->integration->isPlanEligible('pro'))->toBeTrue();
    expect($this->integration->isPlanEligible('free'))->toBeFalse();
});

// ── Tenant Isolation ────────────────────────────────────────────

test('company integrations are isolated between companies', function (): void {
    $companyB = Company::factory()->create();
    CompanyIntegration::withoutGlobalScopes()->create([
        'company_id' => $companyB->id,
        'integration_id' => $this->integration->id,
        'config' => [],
        'field_maps' => [],
        'status' => 'active',
        'installed_at' => now(),
    ]);

    // Company A context
    CompanyContext::setActive($this->company);
    expect(CompanyIntegration::count())->toBe(1);

    // Company B context
    CompanyContext::setActive($companyB);
    expect(CompanyIntegration::count())->toBe(1);
});
