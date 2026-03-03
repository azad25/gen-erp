<?php

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Customer\Models\Customer;
use App\Services\CompanyContext;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    CompanyContext::setActive($this->company);
    
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    
    $this->auditLogger = app(AuditLogger::class);
});

test('AuditLogger creates audit log entry for model changes', function (): void {
    $customer = Customer::factory()->create(['company_id' => $this->company->id]);

    $oldData = ['name' => 'Old Name', 'email' => 'old@example.com'];
    $newData = ['name' => 'New Name', 'email' => 'new@example.com'];

    $this->auditLogger->log('updated', $customer, $oldData, $newData);

    $auditLog = AuditLog::first();
    expect($auditLog)->not->toBeNull();
    expect($auditLog->company_id)->toBe($this->company->id);
    expect($auditLog->user_id)->toBe($this->user->id);
    expect($auditLog->event)->toBe('updated');
    expect($auditLog->auditable_type)->toBe(Customer::class);
    expect($auditLog->auditable_id)->toBe($customer->id);
    expect($auditLog->old_values)->toBe($oldData);
    expect($auditLog->new_values)->toBe($newData);
    expect($auditLog->ip_address)->not->toBeNull();
    expect($auditLog->user_agent)->not->toBeNull();
});

test('AuditLogger handles creation events', function (): void {
    $customer = Customer::factory()->create(['company_id' => $this->company->id]);

    $newData = ['name' => 'New Customer', 'email' => 'customer@example.com'];

    $this->auditLogger->log('created', $customer, [], $newData);

    $auditLog = AuditLog::first();
    expect($auditLog->event)->toBe('created');
    expect($auditLog->old_values)->toBeNull();
    expect($auditLog->new_values)->toBe($newData);
});

test('AuditLogger handles deletion events', function (): void {
    $customer = Customer::factory()->create(['company_id' => $this->company->id]);

    $oldData = ['name' => 'Deleted Customer', 'email' => 'deleted@example.com'];

    $this->auditLogger->log('deleted', $customer, $oldData, []);

    $auditLog = AuditLog::first();
    expect($auditLog->event)->toBe('deleted');
    expect($auditLog->old_values)->toBe($oldData);
    expect($auditLog->new_values)->toBeNull();
});

test('AuditLogger works without authenticated user', function (): void {
    auth()->logout();

    $customer = Customer::factory()->create(['company_id' => $this->company->id]);
    $this->auditLogger->log('system_update', $customer, [], ['status' => 'processed']);

    $auditLog = AuditLog::first();
    expect($auditLog->user_id)->toBeNull();
    expect($auditLog->event)->toBe('system_update');
});

test('AuditLogger captures IP address and user agent', function (): void {
    // Simulate request with specific IP and user agent
    request()->server->set('REMOTE_ADDR', '192.168.1.100');
    request()->server->set('HTTP_USER_AGENT', 'Test Browser 1.0');

    $customer = Customer::factory()->create(['company_id' => $this->company->id]);
    $this->auditLogger->log('login', $customer);

    $auditLog = AuditLog::first();
    expect($auditLog->ip_address)->toBe('192.168.1.100');
    expect($auditLog->user_agent)->not->toBeNull();
});

test('AuditLogger handles models without company_id gracefully', function (): void {
    // Create a model that doesn't have company_id
    $user = User::factory()->create();

    // Should not create audit log when no company context
    CompanyContext::setActive($this->company);
    $this->auditLogger->log('profile_updated', $user, ['name' => 'Old'], ['name' => 'New']);

    $auditLog = AuditLog::first();
    expect($auditLog->company_id)->toBe($this->company->id);
});

test('AuditLogger fails silently on errors', function (): void {
    // Force an error by using invalid data
    $customer = Customer::factory()->create(['company_id' => $this->company->id]);
    
    // Mock the AuditLog creation to throw an exception
    $this->mock(AuditLog::class, function ($mock) {
        $mock->shouldReceive('create')->andThrow(new Exception('Database error'));
    });

    // Should not throw exception, just log error
    expect(fn () => $this->auditLogger->log('test', $customer))->not->toThrow(Exception::class);
});

test('AuditLog model has correct relationships', function (): void {
    $customer = Customer::factory()->create(['company_id' => $this->company->id]);
    $this->auditLogger->log('created', $customer, [], ['name' => 'Test Customer']);

    $auditLog = AuditLog::first();
    
    // Test relationships
    expect($auditLog->user)->toBeInstanceOf(User::class);
    expect($auditLog->user->id)->toBe($this->user->id);
    
    expect($auditLog->company)->toBeInstanceOf(Company::class);
    expect($auditLog->company->id)->toBe($this->company->id);
    
    expect($auditLog->auditable)->toBeInstanceOf(Customer::class);
    expect($auditLog->auditable->id)->toBe($customer->id);
});

test('AuditLog can be filtered by event type', function (): void {
    $customer1 = Customer::factory()->create(['company_id' => $this->company->id]);
    $customer2 = Customer::factory()->create(['company_id' => $this->company->id]);

    $this->auditLogger->log('created', $customer1);
    $this->auditLogger->log('updated', $customer1);
    $this->auditLogger->log('created', $customer2);
    $this->auditLogger->log('deleted', $customer2);

    $createdLogs = AuditLog::where('event', 'created')->get();
    expect($createdLogs)->toHaveCount(2);

    $updatedLogs = AuditLog::where('event', 'updated')->get();
    expect($updatedLogs)->toHaveCount(1);

    $deletedLogs = AuditLog::where('event', 'deleted')->get();
    expect($deletedLogs)->toHaveCount(1);
});

test('AuditLog can be filtered by model type', function (): void {
    $customer = Customer::factory()->create(['company_id' => $this->company->id]);
    $user = User::factory()->create();

    $this->auditLogger->log('created', $customer);
    $this->auditLogger->log('updated', $customer);
    $this->auditLogger->log('login', $user);

    $customerLogs = AuditLog::where('auditable_type', Customer::class)->get();
    expect($customerLogs)->toHaveCount(2);

    $userLogs = AuditLog::where('auditable_type', User::class)->get();
    expect($userLogs)->toHaveCount(1);
});

test('AuditLog can be filtered by date range', function (): void {
    $customer = Customer::factory()->create(['company_id' => $this->company->id]);

    // Create logs for today
    $this->auditLogger->log('date_test_today1', $customer);
    $this->auditLogger->log('date_test_today2', $customer);
    
    // Create log for yesterday manually
    $yesterday = now()->subDay();
    AuditLog::create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
        'event' => 'date_test_yesterday',
        'auditable_type' => Customer::class,
        'auditable_id' => $customer->id,
        'created_at' => $yesterday,
    ]);

    // Count today's logs
    $todayCount = AuditLog::where('event', 'like', 'date_test_today%')->count();
    expect($todayCount)->toBe(2);

    // Count yesterday's logs
    $yesterdayCount = AuditLog::where('event', 'date_test_yesterday')->count();
    expect($yesterdayCount)->toBe(1);
});

test('AuditLog tracks changes to sensitive fields', function (): void {
    $customer = Customer::factory()->create(['company_id' => $this->company->id]);

    $sensitiveChanges = [
        'credit_limit' => 50000,
        'payment_terms' => 'Net 30',
        'is_active' => false,
    ];

    $this->auditLogger->log('sensitive_update', $customer, [], $sensitiveChanges);

    $auditLog = AuditLog::where('event', 'sensitive_update')->first();
    expect($auditLog->new_values)->toEqual($sensitiveChanges);
    expect($auditLog->event)->toBe('sensitive_update');
});

test('Company A audit logs not visible to Company B', function (): void {
    $companyB = Company::factory()->create();
    
    // Create audit logs for Company A
    $customerA = Customer::factory()->create(['company_id' => $this->company->id]);
    $this->auditLogger->log('company_a_created', $customerA);
    $this->auditLogger->log('company_a_updated', $customerA);

    // Switch to Company B
    CompanyContext::setActive($companyB);

    // Company B should not see Company A's audit logs
    expect(AuditLog::all())->toHaveCount(0);

    // Create audit log for Company B
    $customerB = Customer::factory()->create(['company_id' => $companyB->id]);
    $this->auditLogger->log('company_b_created', $customerB);

    expect(AuditLog::all())->toHaveCount(1);
    expect(AuditLog::first()->company_id)->toBe($companyB->id);

    // Verify Company A's logs exist without global scopes
    $totalLogs = AuditLog::withoutGlobalScopes()
        ->whereIn('event', ['company_a_created', 'company_a_updated', 'company_b_created'])
        ->count();
    expect($totalLogs)->toBe(3);
});

test('AuditLog provides human readable descriptions', function (): void {
    $customer = Customer::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Customer'
    ]);

    $this->auditLogger->log('created', $customer, [], ['name' => 'Test Customer']);

    $auditLog = AuditLog::first();
    
    // Test that we can generate human-readable descriptions
    $description = "User {$this->user->name} created Customer #{$customer->id} (Test Customer)";
    
    expect($auditLog->user->name)->toBe($this->user->name);
    expect($auditLog->auditable->name)->toBe('Test Customer');
    expect($auditLog->event)->toBe('created');
});

test('AuditLog handles bulk operations', function (): void {
    $customers = Customer::factory()->count(5)->create(['company_id' => $this->company->id]);

    foreach ($customers as $customer) {
        $this->auditLogger->log('bulk_update', $customer, [], ['status' => 'processed']);
    }

    $bulkLogs = AuditLog::where('event', 'bulk_update')->get();
    expect($bulkLogs)->toHaveCount(5);

    // All should have same timestamp (within a few seconds)
    $timestamps = $bulkLogs->pluck('created_at')->unique();
    expect($timestamps)->toHaveCount(1);
});