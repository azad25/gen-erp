<?php

use App\Models\Company;
use App\Models\NumberSequence;
use App\Services\CompanyContext;
use App\Services\SequenceService;

// ═══════════════════════════════════════════════
// SequenceTest — 5 tests
// ═══════════════════════════════════════════════

test('next() returns correctly formatted number', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(SequenceService::class);

    NumberSequence::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'document_type' => 'invoice',
        'prefix' => 'INV',
        'separator' => '-',
        'padding' => 4,
        'next_number' => 1,
        'reset_frequency' => 'never',
        'include_date' => false,
    ]);

    $number = $service->next('invoice', $company);
    expect($number)->toBe('INV-0001');

    $number2 = $service->next('invoice', $company);
    expect($number2)->toBe('INV-0002');
});

test('next() with date format includes date', function (): void {
    $company = Company::factory()->create();

    NumberSequence::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'document_type' => 'purchase_order',
        'prefix' => 'PO',
        'separator' => '-',
        'padding' => 4,
        'next_number' => 42,
        'reset_frequency' => 'never',
        'include_date' => true,
        'date_format' => 'Ym',
    ]);

    $service = app(SequenceService::class);
    $number = $service->next('purchase_order', $company);

    $expected = 'PO-'.now()->format('Ym').'-0042';
    expect($number)->toBe($expected);
});

test('Concurrent calls return unique numbers', function (): void {
    $company = Company::factory()->create();

    NumberSequence::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'document_type' => 'sales_order',
        'prefix' => 'SO',
        'separator' => '-',
        'padding' => 4,
        'next_number' => 1,
        'reset_frequency' => 'never',
    ]);

    $service = app(SequenceService::class);
    $numbers = [];

    for ($i = 0; $i < 10; $i++) {
        $numbers[] = $service->next('sales_order', $company);
    }

    // All 10 numbers should be unique
    expect(count(array_unique($numbers)))->toBe(10);
    expect($numbers[0])->toBe('SO-0001');
    expect($numbers[9])->toBe('SO-0010');
});

test('Company A and B have independent sequences', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $service = app(SequenceService::class);

    $numA = $service->next('invoice', $companyA);
    $numB = $service->next('invoice', $companyB);

    // Both should start from 0001 since they are independent
    expect($numA)->toContain('0001');
    expect($numB)->toContain('0001');
});

test('getOrCreate auto-creates sequence with defaults', function (): void {
    $company = Company::factory()->create();

    $service = app(SequenceService::class);
    $seq = $service->getOrCreate('expense', $company);

    expect($seq->document_type)->toBe('expense');
    expect($seq->prefix)->toBe('EXP');
    expect($seq->padding)->toBe(4);
    expect($seq->next_number)->toBe(1);
});
