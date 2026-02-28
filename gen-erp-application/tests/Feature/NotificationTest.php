<?php

use App\Enums\NotificationEvent;
use App\Models\Company;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\NotificationService;

// ═══════════════════════════════════════════════
// NotificationTest — 6 tests
// ═══════════════════════════════════════════════

test('render() replaces {variable} placeholders correctly', function (): void {
    $service = app(NotificationService::class);

    $result = $service->render(
        'Invoice {invoice_number} for {customer_name} totalling {total_amount}',
        ['{invoice_number}' => 'INV-001', '{customer_name}' => 'ABC Corp', '{total_amount}' => '৳1,000'],
    );

    expect($result)->toBe('Invoice INV-001 for ABC Corp totalling ৳1,000');
});

test('Unknown {variables} render as empty string', function (): void {
    $service = app(NotificationService::class);

    $result = $service->render('Hello {name}, status: {unknown_var}', ['{name}' => 'Bob']);

    expect($result)->toBe('Hello Bob, status: ');
});

test('User preference in_app=false: in_app notification not sent', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);

    // Create template
    NotificationTemplate::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'event_key' => NotificationEvent::INVOICE_SENT->value,
        'channel' => 'in_app',
        'body' => 'Invoice {invoice_number} sent.',
        'is_active' => true,
    ]);

    // Set preference: in_app = false
    \Illuminate\Support\Facades\DB::table('notification_preferences')->insert([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'event_key' => NotificationEvent::INVOICE_SENT->value,
        'in_app' => false,
        'email' => true,
        'sms' => false,
    ]);

    $service = app(NotificationService::class);
    $service->send(NotificationEvent::INVOICE_SENT, $company, ['{invoice_number}' => 'INV-001'], [$user->id]);

    // User should have NO database notification since in_app=false
    expect($user->notifications()->count())->toBe(0);
});

test('Company template is used for notification body', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);

    // Company-specific template
    NotificationTemplate::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'event_key' => NotificationEvent::PAYMENT_RECEIVED->value,
        'channel' => 'in_app',
        'body' => 'Custom template: Payment {receipt_number} received!',
        'is_active' => true,
    ]);

    $service = app(NotificationService::class);
    $service->send(NotificationEvent::PAYMENT_RECEIVED, $company, ['{receipt_number}' => 'REC-001'], [$user->id]);

    $notification = $user->notifications()->first();
    expect($notification->data['body'])->toContain('Custom template');
    expect($notification->data['body'])->toContain('REC-001');
});

test('sendToRole sends to all matching role users', function (): void {
    $company = Company::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    $user1->companies()->attach($company->id, ['role' => 'sales']);
    $user2->companies()->attach($company->id, ['role' => 'sales']);
    $user3->companies()->attach($company->id, ['role' => 'admin']);

    NotificationTemplate::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'event_key' => NotificationEvent::LOW_STOCK->value,
        'channel' => 'in_app',
        'body' => '{product_name} is low stock.',
        'is_active' => true,
    ]);

    $service = app(NotificationService::class);
    $service->sendToRole(NotificationEvent::LOW_STOCK, $company, ['{product_name}' => 'Widget'], 'sales');

    expect($user1->notifications()->count())->toBe(1);
    expect($user2->notifications()->count())->toBe(1);
    expect($user3->notifications()->count())->toBe(0); // admin, not sales
});

test('NotificationEvent enum has correct labels and variables', function (): void {
    expect(NotificationEvent::INVOICE_SENT->label())->toBe('Invoice Sent');
    expect(NotificationEvent::INVOICE_SENT->availableVariables())->toContain('{invoice_number}');
    expect(NotificationEvent::options())->toHaveKey('invoice_sent');
});
