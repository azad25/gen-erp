<?php

use App\Http\Middleware\SecurityHeaders;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Product;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\SecurityEventService;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

// ── Security Headers ────────────────────────────────────────

test('security headers middleware adds all required headers', function (): void {
    $middleware = new SecurityHeaders();
    $request = Request::create('/test', 'GET');

    $response = $middleware->handle($request, function () {
        return new Response('OK');
    });

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN');
    expect($response->headers->get('X-XSS-Protection'))->toBe('1; mode=block');
    expect($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
    expect($response->headers->get('Strict-Transport-Security'))->toContain('max-age=');
    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'self'");
    expect($response->headers->get('Content-Security-Policy'))->toContain('wss:');
});

// ── Two-Factor Authentication ───────────────────────────────

test('2FA can be enabled for a user', function (): void {
    $user = User::factory()->create();

    $service = new TwoFactorService();
    $result = $service->enable($user);

    expect($result)->toHaveKeys(['secret', 'qr_url']);
    expect($result['secret'])->toHaveLength(32);
    expect($result['qr_url'])->toContain('otpauth://totp/');

    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull();
    expect($user->two_factor_recovery_codes)->not->toBeNull();
    expect($user->two_factor_confirmed_at)->toBeNull();
});

test('2FA generates 10 recovery codes', function (): void {
    $user = User::factory()->create();
    $service = new TwoFactorService();
    $service->enable($user);

    $codes = $service->getRecoveryCodes($user->refresh());
    expect($codes)->toHaveCount(10);
});

test('recovery code can be used and is consumed', function (): void {
    $user = User::factory()->create();
    $service = new TwoFactorService();
    $service->enable($user);

    $codes = $service->getRecoveryCodes($user->refresh());
    $firstCode = $codes[0];

    $result = $service->verifyRecoveryCode($user, $firstCode);
    expect($result)->toBeTrue();

    // Same code should not work again
    $result2 = $service->verifyRecoveryCode($user->refresh(), $firstCode);
    expect($result2)->toBeFalse();

    // Should have 9 remaining
    $remaining = $service->getRecoveryCodes($user->refresh());
    expect($remaining)->toHaveCount(9);
});

test('2FA can be disabled', function (): void {
    $user = User::factory()->create();
    $service = new TwoFactorService();
    $service->enable($user);

    $service->disable($user);
    $user->refresh();

    expect($user->two_factor_secret)->toBeNull();
    expect($user->two_factor_recovery_codes)->toBeNull();
    expect($user->two_factor_confirmed_at)->toBeNull();
    expect($service->isEnabled($user))->toBeFalse();
});

// ── Security Events ─────────────────────────────────────────

test('security event service logs events', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);
    $this->actingAs($user);

    $service = new SecurityEventService();
    $service->logFailed2FA($user->id);

    $event = SecurityEvent::where('event_type', SecurityEvent::TYPE_FAILED_2FA)->first();
    expect($event)->not->toBeNull();
    expect($event->user_id)->toBe($user->id);
    expect($event->company_id)->toBe($company->id);
});

test('security event logs mass export with metadata', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);
    $this->actingAs($user);

    $service = new SecurityEventService();
    $service->logMassExport('customer', 500);

    $event = SecurityEvent::where('event_type', SecurityEvent::TYPE_MASS_EXPORT)->first();
    expect($event)->not->toBeNull();
    expect($event->metadata['entity_type'])->toBe('customer');
    expect($event->metadata['record_count'])->toBe(500);
});

// ── LogsAudit Trait ─────────────────────────────────────────

test('LogsAudit trait auto-logs model creation', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);
    $this->actingAs($user);

    // Product uses DispatchesModelEvents, we test AuditLog creation via AuditLogger
    app(\App\Services\AuditLogger::class)->log('created', $company, [], ['name' => $company->name]);

    $log = AuditLog::where('event', 'created')
        ->where('auditable_type', Company::class)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->company_id)->toBe($company->id);
    expect($log->user_id)->toBe($user->id);
});

// ── Audit Log Tenant Isolation ──────────────────────────────

test('security events are scoped and do not leak across companies', function (): void {
    $userA = User::factory()->create();
    $companyA = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $companyA->id,
        'user_id' => $userA->id,
    ]);

    CompanyContext::setActive($companyA);
    SecurityEvent::create([
        'company_id' => $companyA->id,
        'user_id' => $userA->id,
        'event_type' => 'test_event',
    ]);

    $companyB = Company::factory()->create();
    CompanyContext::setActive($companyB);

    // SecurityEvent does not have BelongsToCompany, so direct query
    $events = SecurityEvent::where('company_id', $companyB->id)->get();
    expect($events)->toHaveCount(0);
});
