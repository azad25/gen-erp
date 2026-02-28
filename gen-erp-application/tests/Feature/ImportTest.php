<?php

use App\Models\Company;
use App\Models\User;
use App\Services\ImportService;

// ═══════════════════════════════════════════════
// ImportTest — 5 tests
// ═══════════════════════════════════════════════

test('queueImport creates ImportJob with pending status', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $service = app(ImportService::class);
    $job = $service->queueImport($company, 'products', '/tmp/test.csv', $user, 'products.csv');

    expect($job->status)->toBe('pending');
    expect($job->entity_type)->toBe('products');
    expect($job->company_id)->toBe($company->id);
});

test('queueImport throws for unsupported entity type', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $service = app(ImportService::class);
    $service->queueImport($company, 'invalid_type', '/tmp/test.csv', $user, 'test.csv');
})->throws(InvalidArgumentException::class, 'Unsupported entity type');

test('markProcessing updates status and total_rows', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $service = app(ImportService::class);
    $job = $service->queueImport($company, 'customers', '/tmp/test.csv', $user, 'customers.csv');

    $service->markProcessing($job, 100);
    $job->refresh();

    expect($job->status)->toBe('processing');
    expect($job->total_rows)->toBe(100);
    expect($job->started_at)->not->toBeNull();
});

test('recordSuccess and recordFailure update counts correctly', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $service = app(ImportService::class);
    $job = $service->queueImport($company, 'suppliers', '/tmp/test.csv', $user, 'suppliers.csv');
    $service->markProcessing($job, 5);

    // 3 successes
    $service->recordSuccess($job);
    $service->recordSuccess($job);
    $service->recordSuccess($job);

    // 2 failures
    $service->recordFailure($job, ['row' => 4, 'field' => 'phone', 'message' => 'Invalid BD phone number']);
    $service->recordFailure($job, ['row' => 5, 'field' => 'name', 'message' => 'Name is required']);

    $job->refresh();

    expect($job->processed_rows)->toBe(5);
    expect($job->created_rows)->toBe(3);
    expect($job->failed_rows)->toBe(2);
    expect($job->errors)->toHaveCount(2);
    expect($job->errors[0]['message'])->toBe('Invalid BD phone number');
});

test('markCompleted sets status and completed_at', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $service = app(ImportService::class);
    $job = $service->queueImport($company, 'employees', '/tmp/test.csv', $user, 'employees.csv');
    $service->markProcessing($job, 10);
    $service->recordSuccess($job);

    $service->markCompleted($job);
    $job->refresh();

    expect($job->status)->toBe('completed');
    expect($job->completed_at)->not->toBeNull();
});
