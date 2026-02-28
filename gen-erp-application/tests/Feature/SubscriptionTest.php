<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentRequestStatus;
use App\Enums\SubscriptionStatus;
use App\Exceptions\PlanLimitExceededException;
use App\Models\Company;
use App\Models\PaymentRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageCounter;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\SubscriptionService;
use App\Services\UsageCounterService;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    CompanyContext::setActive($this->company);
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company->id, ['role' => 'owner']);

    // Seed plans
    $this->seed(\Database\Seeders\PlanSeeder::class);
    $this->freePlan = Plan::bySlug('free');
    $this->proPlan = Plan::bySlug('pro');
    $this->enterprisePlan = Plan::bySlug('enterprise');
});

// ── Plan Model ──────────────────────────────────────────────────

test('Plan seeder creates 3 plans with correct limits', function (): void {
    expect(Plan::count())->toBe(3);
    expect($this->freePlan->getLimit('products'))->toBe(50);
    expect($this->freePlan->getLimit('users'))->toBe(2);
    expect($this->proPlan->getLimit('products'))->toBe(-1); // unlimited
    expect($this->proPlan->hasFeature('api_access'))->toBeTrue();
    expect($this->freePlan->hasFeature('api_access'))->toBeFalse();
    expect($this->enterprisePlan->hasFeature('plugin_sdk'))->toBeTrue();
});

test('Plan formatted prices display correctly in BDT', function (): void {
    expect($this->freePlan->formattedMonthlyPrice())->toBe('Free');
    expect($this->proPlan->formattedMonthlyPrice())->toContain('৳');
    expect($this->enterprisePlan->formattedAnnualPrice())->toContain('৳');
});

// ── SubscriptionService ─────────────────────────────────────────

test('company without subscription gets free plan by default', function (): void {
    $service = app(SubscriptionService::class);

    $plan = $service->getActivePlan($this->company->id);
    expect($plan->slug)->toBe('free');
    expect($service->isAccessible($this->company->id))->toBeTrue();
    expect($service->isReadOnly($this->company->id))->toBeFalse();
});

test('activating a subscription sets plan and creates counters', function (): void {
    $service = app(SubscriptionService::class);

    $subscription = $service->activate($this->company->id, $this->proPlan->id, 'monthly');

    expect($subscription->status)->toBe(SubscriptionStatus::ACTIVE);
    expect($subscription->plan_id)->toBe($this->proPlan->id);
    expect($subscription->ends_at)->not->toBeNull();

    // Company plan updated
    $this->company->refresh();
    expect($this->company->plan->value)->toBe('pro');

    // Usage counters initialized
    $counters = UsageCounter::withoutGlobalScopes()
        ->where('company_id', $this->company->id)
        ->pluck('max_value', 'counter_key');
    expect($counters['products'])->toBe(-1); // unlimited for pro
    expect($counters['users'])->toBe(10);
});

test('verifying payment activates subscription and creates invoice', function (): void {
    $service = app(SubscriptionService::class);

    $request = PaymentRequest::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'plan_id' => $this->proPlan->id,
        'billing_cycle' => 'monthly',
        'amount' => $this->proPlan->monthly_price,
        'method' => PaymentMethod::BKASH,
        'transaction_id' => 'TXN123456',
        'status' => PaymentRequestStatus::PENDING,
        'submitted_by' => $this->user->id,
    ]);

    $subscription = $service->verifyPayment($request, $this->user->id);

    $request->refresh();
    expect($request->status)->toBe(PaymentRequestStatus::VERIFIED);
    expect($subscription->status)->toBe(SubscriptionStatus::ACTIVE);

    // Invoice created
    $invoice = $subscription->invoices()->first();
    expect($invoice)->not->toBeNull();
    expect($invoice->amount)->toBe($this->proPlan->monthly_price);
});

test('rejecting payment updates status with note', function (): void {
    $service = app(SubscriptionService::class);

    $request = PaymentRequest::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'plan_id' => $this->proPlan->id,
        'billing_cycle' => 'monthly',
        'amount' => $this->proPlan->monthly_price,
        'method' => PaymentMethod::NAGAD,
        'status' => PaymentRequestStatus::PENDING,
        'submitted_by' => $this->user->id,
    ]);

    $service->rejectPayment($request, $this->user->id, 'Invalid transaction ID');

    $request->refresh();
    expect($request->status)->toBe(PaymentRequestStatus::REJECTED);
    expect($request->admin_note)->toBe('Invalid transaction ID');
});

// ── Expiry Lifecycle ────────────────────────────────────────────

test('processExpiries transitions Active→Grace→Expired', function (): void {
    $service = app(SubscriptionService::class);

    // Create a subscription that ended yesterday
    $sub = Subscription::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'plan_id' => $this->proPlan->id,
        'status' => SubscriptionStatus::ACTIVE,
        'billing_cycle' => 'monthly',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subDay(),
        'grace_ends_at' => now()->addDays(6),
    ]);

    // Run expiry: Active → Grace
    $stats = $service->processExpiries();
    expect($stats['to_grace'])->toBe(1);
    $sub->refresh();
    expect($sub->status)->toBe(SubscriptionStatus::GRACE);

    // Set grace_ends_at to yesterday
    $sub->update(['grace_ends_at' => now()->subDay()]);

    // Run expiry: Grace → Expired
    $stats = $service->processExpiries();
    expect($stats['to_expired'])->toBe(1);
    $sub->refresh();
    expect($sub->status)->toBe(SubscriptionStatus::EXPIRED);

    // Company downgraded to free
    $this->company->refresh();
    expect($this->company->plan->value)->toBe('free');
});

test('expired subscription puts company in read-only mode', function (): void {
    $service = app(SubscriptionService::class);

    Subscription::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'plan_id' => $this->proPlan->id,
        'status' => SubscriptionStatus::EXPIRED,
        'billing_cycle' => 'monthly',
        'starts_at' => now()->subMonths(2),
        'ends_at' => now()->subMonth(),
    ]);

    expect($service->isReadOnly($this->company->id))->toBeTrue();
    expect($service->isAccessible($this->company->id))->toBeFalse();
});

// ── UsageCounterService ─────────────────────────────────────────

test('usage counter increments and enforces limits', function (): void {
    $service = app(UsageCounterService::class);

    // Set limit to 3
    UsageCounter::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'counter_key' => 'products',
        'current_value' => 0,
        'max_value' => 3,
    ]);

    $service->increment($this->company->id, 'products');
    $service->increment($this->company->id, 'products');
    $service->increment($this->company->id, 'products');

    // Should now be at limit
    expect($service->wouldExceed($this->company->id, 'products'))->toBeTrue();

    // Should throw
    expect(fn () => $service->increment($this->company->id, 'products'))
        ->toThrow(PlanLimitExceededException::class);
});

test('usage counter decrement works and does not go below zero', function (): void {
    $service = app(UsageCounterService::class);

    UsageCounter::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'counter_key' => 'products',
        'current_value' => 2,
        'max_value' => 10,
    ]);

    $counter = $service->decrement($this->company->id, 'products', 5);
    expect($counter->current_value)->toBe(0);
});

test('unlimited counter never exceeds', function (): void {
    $service = app(UsageCounterService::class);

    UsageCounter::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'counter_key' => 'products',
        'current_value' => 999999,
        'max_value' => -1, // unlimited
    ]);

    expect($service->wouldExceed($this->company->id, 'products'))->toBeFalse();
});

test('usage summary returns data for all counters', function (): void {
    $service = app(UsageCounterService::class);

    // Initialize counters
    $service->initializeForPlan($this->company->id, $this->freePlan);

    $summary = $service->getUsageSummary($this->company->id);
    expect($summary)->toHaveKey('products');
    expect($summary['products']['max'])->toBe(50);
    expect($summary['products']['percent'])->toBe(0.0);
});

// ── Tenant Isolation ────────────────────────────────────────────

test('subscriptions are isolated between companies', function (): void {
    $companyB = Company::factory()->create();

    Subscription::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'plan_id' => $this->proPlan->id,
        'status' => SubscriptionStatus::ACTIVE,
        'billing_cycle' => 'monthly',
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);

    CompanyContext::setActive($this->company);
    expect(Subscription::count())->toBe(1);

    CompanyContext::setActive($companyB);
    expect(Subscription::count())->toBe(0);
});
