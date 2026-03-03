<?php

use App\Domain\Auth\Models\Company;
use App\Domain\System\Models\NumberSequence;
use App\Domain\System\Services\SequenceService;
use App\Domain\System\Services\TwoFactorService;
use App\Domain\System\Services\UsageCounterService;
use App\Services\CompanyContext;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    CompanyContext::setActive($this->company);
});

// ═══════════════════════════════════════════════════
// SequenceService Tests
// ═══════════════════════════════════════════════════

test('SequenceService generates sequential numbers', function (): void {
    $service = app(SequenceService::class);

    $first = $service->next('invoice', $this->company);
    $second = $service->next('invoice', $this->company);
    $third = $service->next('invoice', $this->company);

    expect($first)->toBe('INV-0001');
    expect($second)->toBe('INV-0002');
    expect($third)->toBe('INV-0003');
});

test('SequenceService creates different sequences for different document types', function (): void {
    $service = app(SequenceService::class);

    $invoice1 = $service->next('invoice', $this->company);
    $po1 = $service->next('purchase_order', $this->company);
    $invoice2 = $service->next('invoice', $this->company);
    $po2 = $service->next('purchase_order', $this->company);

    expect($invoice1)->toBe('INV-0001');
    expect($po1)->toBe('PO-0001');
    expect($invoice2)->toBe('INV-0002');
    expect($po2)->toBe('PO-0002');
});

test('SequenceService uses correct prefixes for document types', function (): void {
    $service = app(SequenceService::class);

    expect($service->next('customer', $this->company))->toBe('CUST-0001');
    expect($service->next('supplier', $this->company))->toBe('SUPP-0001');
    expect($service->next('sales_order', $this->company))->toBe('SO-0001');
    expect($service->next('goods_receipt', $this->company))->toBe('GRN-0001');
    expect($service->next('payment', $this->company))->toBe('RCP-0001');
});

test('SequenceService creates custom sequence configuration', function (): void {
    $service = app(SequenceService::class);

    // Create custom sequence
    $sequence = NumberSequence::create([
        'company_id' => $this->company->id,
        'document_type' => 'custom_doc',
        'prefix' => 'CUSTOM',
        'separator' => '_',
        'padding' => 6,
        'next_number' => 100,
        'suffix' => 'END',
        'include_date' => false,
    ]);

    $number = $service->next('custom_doc', $this->company);
    expect($number)->toBe('CUSTOM_000100_END');

    $nextNumber = $service->next('custom_doc', $this->company);
    expect($nextNumber)->toBe('CUSTOM_000101_END');
});

test('SequenceService includes date when configured', function (): void {
    $service = app(SequenceService::class);

    // Create sequence with date
    NumberSequence::create([
        'company_id' => $this->company->id,
        'document_type' => 'dated_doc',
        'prefix' => 'DOC',
        'separator' => '-',
        'padding' => 3,
        'next_number' => 1,
        'include_date' => true,
        'date_format' => 'Ymd',
    ]);

    $number = $service->next('dated_doc', $this->company);
    $expectedDate = now()->format('Ymd');
    expect($number)->toBe("DOC-{$expectedDate}-001");
});

test('SequenceService resets yearly when configured', function (): void {
    $service = app(SequenceService::class);

    // Create sequence with yearly reset
    $sequence = NumberSequence::create([
        'company_id' => $this->company->id,
        'document_type' => 'yearly_doc',
        'prefix' => 'YR',
        'separator' => '-',
        'padding' => 2,
        'next_number' => 5,
        'reset_frequency' => 'yearly',
        'last_reset_at' => now()->subYear()->toDateString(), // Last year
    ]);

    $number = $service->next('yearly_doc', $this->company);
    expect($number)->toBe('YR-01'); // Should reset to 1

    $sequence->refresh();
    expect($sequence->next_number)->toBe(2);
    expect($sequence->last_reset_at->toDateString())->toBe(now()->toDateString());
});

test('SequenceService preview shows next number without incrementing', function (): void {
    $service = app(SequenceService::class);

    $sequence = $service->getOrCreate('test_doc', $this->company);
    
    $preview1 = $service->preview($sequence);
    $preview2 = $service->preview($sequence);
    
    expect($preview1)->toBe('TES-0001');
    expect($preview2)->toBe('TES-0001'); // Same number

    $actual = $service->next('test_doc', $this->company);
    expect($actual)->toBe('TES-0001');
});

test('SequenceService is atomic and thread-safe', function (): void {
    $service = app(SequenceService::class);

    // Simulate concurrent access
    $numbers = [];
    for ($i = 0; $i < 10; $i++) {
        $numbers[] = $service->next('concurrent_test', $this->company);
    }

    // All numbers should be unique and sequential
    expect($numbers)->toHaveCount(10);
    expect(array_unique($numbers))->toHaveCount(10);
    expect($numbers[0])->toBe('CON-0001');
    expect($numbers[9])->toBe('CON-0010');
});

test('Company A sequences not visible to Company B', function (): void {
    $companyB = Company::factory()->create();
    $service = app(SequenceService::class);

    // Generate numbers for Company A
    $service->next('invoice', $this->company);
    $service->next('invoice', $this->company);

    // Switch to Company B
    CompanyContext::setActive($companyB);

    // Company B should start from 1
    $numberB = $service->next('invoice', $companyB);
    expect($numberB)->toBe('INV-0001');

    // Verify Company A sequences are isolated
    expect(NumberSequence::all())->toHaveCount(1); // Only Company B's sequence visible
    expect(NumberSequence::withoutGlobalScopes()->count())->toBe(2); // Both exist
});

// ═══════════════════════════════════════════════════
// TwoFactorService Tests
// ═══════════════════════════════════════════════════

test('TwoFactorService generates valid TOTP secret', function (): void {
    $service = app(TwoFactorService::class);

    $secret = $service->generateSecret();

    expect($secret)->toBeString();
    expect(strlen($secret))->toBe(32); // Base32 encoded secret
    expect($secret)->toMatch('/^[A-Z2-7]+$/'); // Valid Base32 characters
});

test('TwoFactorService generates QR code URL', function (): void {
    $service = app(TwoFactorService::class);

    $secret = $service->generateSecret();
    $qrUrl = $service->getQrCodeUrl('test@example.com', 'Test App', $secret);

    expect($qrUrl)->toBeString();
    expect($qrUrl)->toStartWith('https://api.qrserver.com/v1/create-qr-code/');
    expect($qrUrl)->toContain('otpauth');
    expect($qrUrl)->toContain($secret);
});

test('TwoFactorService validates correct TOTP code', function (): void {
    $service = app(TwoFactorService::class);

    $secret = 'JBSWY3DPEHPK3PXP'; // Known test secret
    $timestamp = 1234567890; // Fixed timestamp for predictable code

    // Generate code for the timestamp
    $code = $service->generateCodeForTimestamp($secret, $timestamp);
    
    // Validate the code
    $isValid = $service->validateCode($secret, $code, $timestamp);
    expect($isValid)->toBeTrue();
});

test('TwoFactorService rejects invalid TOTP code', function (): void {
    $service = app(TwoFactorService::class);

    $secret = 'JBSWY3DPEHPK3PXP';
    $timestamp = time();

    $isValid = $service->validateCode($secret, '000000', $timestamp);
    expect($isValid)->toBeFalse();

    $isValid = $service->validateCode($secret, '123456', $timestamp);
    expect($isValid)->toBeFalse();
});

test('TwoFactorService allows time window tolerance', function (): void {
    $service = app(TwoFactorService::class);

    $secret = 'JBSWY3DPEHPK3PXP';
    $baseTimestamp = 1234567890;

    // Generate code for base timestamp
    $code = $service->generateCodeForTimestamp($secret, $baseTimestamp);

    // Should be valid within time window (±30 seconds)
    expect($service->validateCode($secret, $code, $baseTimestamp - 30))->toBeTrue();
    expect($service->validateCode($secret, $code, $baseTimestamp + 30))->toBeTrue();

    // Should be invalid outside time window
    expect($service->validateCode($secret, $code, $baseTimestamp - 60))->toBeFalse();
    expect($service->validateCode($secret, $code, $baseTimestamp + 60))->toBeFalse();
});

// ═══════════════════════════════════════════════════
// UsageCounterService Tests  
// ═══════════════════════════════════════════════════

test('UsageCounterService tracks usage counters', function (): void {
    $service = app(UsageCounterService::class);

    // Initialize counters
    $service->initializeCounter($this->company->id, 'products', 100);
    $service->initializeCounter($this->company->id, 'users', 5);

    expect($service->getCurrentUsage($this->company->id, 'products'))->toBe(0);
    expect($service->getLimit($this->company->id, 'products'))->toBe(100);
    expect($service->getRemainingUsage($this->company->id, 'products'))->toBe(100);
});

test('UsageCounterService increments usage correctly', function (): void {
    $service = app(UsageCounterService::class);

    $service->initializeCounter($this->company->id, 'invoices', 50);

    $service->incrementUsage($this->company->id, 'invoices');
    expect($service->getCurrentUsage($this->company->id, 'invoices'))->toBe(1);

    $service->incrementUsage($this->company->id, 'invoices', 5);
    expect($service->getCurrentUsage($this->company->id, 'invoices'))->toBe(6);

    expect($service->getRemainingUsage($this->company->id, 'invoices'))->toBe(44);
});

test('UsageCounterService decrements usage correctly', function (): void {
    $service = app(UsageCounterService::class);

    $service->initializeCounter($this->company->id, 'orders', 25);
    $service->incrementUsage($this->company->id, 'orders', 10);

    $service->decrementUsage($this->company->id, 'orders', 3);
    expect($service->getCurrentUsage($this->company->id, 'orders'))->toBe(7);

    $service->decrementUsage($this->company->id, 'orders');
    expect($service->getCurrentUsage($this->company->id, 'orders'))->toBe(6);
});

test('UsageCounterService checks if limit is exceeded', function (): void {
    $service = app(UsageCounterService::class);

    $service->initializeCounter($this->company->id, 'storage', 10);

    expect($service->isLimitExceeded($this->company->id, 'storage'))->toBeFalse();

    $service->incrementUsage($this->company->id, 'storage', 10);
    expect($service->isLimitExceeded($this->company->id, 'storage'))->toBeFalse(); // At limit

    $service->incrementUsage($this->company->id, 'storage', 1);
    expect($service->isLimitExceeded($this->company->id, 'storage'))->toBeTrue(); // Over limit
});

test('UsageCounterService handles unlimited counters', function (): void {
    $service = app(UsageCounterService::class);

    $service->initializeCounter($this->company->id, 'unlimited', -1); // -1 = unlimited

    expect($service->getLimit($this->company->id, 'unlimited'))->toBe(-1);
    expect($service->isLimitExceeded($this->company->id, 'unlimited'))->toBeFalse();

    $service->incrementUsage($this->company->id, 'unlimited', 1000000);
    expect($service->isLimitExceeded($this->company->id, 'unlimited'))->toBeFalse();
    expect($service->getRemainingUsage($this->company->id, 'unlimited'))->toBe(-1);
});

test('UsageCounterService resets counters', function (): void {
    $service = app(UsageCounterService::class);

    $service->initializeCounter($this->company->id, 'monthly', 100);
    $service->incrementUsage($this->company->id, 'monthly', 50);

    expect($service->getCurrentUsage($this->company->id, 'monthly'))->toBe(50);

    $service->resetUsage($this->company->id, 'monthly');
    expect($service->getCurrentUsage($this->company->id, 'monthly'))->toBe(0);
    expect($service->getLimit($this->company->id, 'monthly'))->toBe(100); // Limit unchanged
});

test('UsageCounterService updates limits', function (): void {
    $service = app(UsageCounterService::class);

    $service->initializeCounter($this->company->id, 'flexible', 50);
    $service->incrementUsage($this->company->id, 'flexible', 30);

    $service->updateLimit($this->company->id, 'flexible', 100);

    expect($service->getLimit($this->company->id, 'flexible'))->toBe(100);
    expect($service->getCurrentUsage($this->company->id, 'flexible'))->toBe(30);
    expect($service->getRemainingUsage($this->company->id, 'flexible'))->toBe(70);
});

test('Company A usage counters not visible to Company B', function (): void {
    $companyB = Company::factory()->create();
    $service = app(UsageCounterService::class);

    // Set up counters for Company A
    $service->initializeCounter($this->company->id, 'products', 100);
    $service->incrementUsage($this->company->id, 'products', 25);

    // Company B should not see Company A's counters
    expect($service->getCurrentUsage($companyB->id, 'products'))->toBe(0);
    expect($service->getLimit($companyB->id, 'products'))->toBe(0);

    // Initialize separate counter for Company B
    $service->initializeCounter($companyB->id, 'products', 50);
    expect($service->getLimit($companyB->id, 'products'))->toBe(50);
    expect($service->getCurrentUsage($companyB->id, 'products'))->toBe(0);

    // Verify Company A's data is unchanged
    expect($service->getCurrentUsage($this->company->id, 'products'))->toBe(25);
    expect($service->getLimit($this->company->id, 'products'))->toBe(100);
});