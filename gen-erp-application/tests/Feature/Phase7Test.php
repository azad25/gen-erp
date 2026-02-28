<?php

use App\Events\ImportProgressUpdated;
use App\Events\LowStockAlert;
use App\Events\POSSaleCompleted;
use App\Jobs\ProcessImportJob;
use App\Jobs\SendNotificationJob;
use App\Models\Company;
use App\Models\ImportJob;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\ImportService;
use App\Services\NotificationService;
use App\Services\POSReceiptService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    CompanyContext::setActive($this->company);
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company->id, ['role' => 'owner']);
});

// ── Broadcast Events ────────────────────────────────────────────

test('LowStockAlert broadcasts on company channel', function (): void {
    Event::fake([LowStockAlert::class]);

    event(new LowStockAlert(
        companyId: $this->company->id,
        branchId: 1,
        productId: 5,
        productName: 'Widget A',
        currentQty: 3,
        threshold: 10,
    ));

    Event::assertDispatched(LowStockAlert::class, function (LowStockAlert $e) {
        return $e->companyId === $this->company->id
            && $e->productName === 'Widget A'
            && $e->currentQty === 3;
    });
});

test('POSSaleCompleted broadcasts on company and branch channels', function (): void {
    Event::fake([POSSaleCompleted::class]);

    $event = new POSSaleCompleted(
        companyId: $this->company->id,
        branchId: 2,
        saleId: 10,
        saleNumber: 'POS-0001',
        totalAmount: 150000,
        paymentMethod: 'cash',
    );

    event($event);

    Event::assertDispatched(POSSaleCompleted::class);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(2);
});

test('ImportProgressUpdated broadcasts with progress data', function (): void {
    Event::fake([ImportProgressUpdated::class]);

    event(new ImportProgressUpdated(
        companyId: $this->company->id,
        importJobId: 1,
        processedRows: 50,
        totalRows: 100,
        createdRows: 48,
        failedRows: 2,
        status: 'processing',
    ));

    Event::assertDispatched(ImportProgressUpdated::class, function (ImportProgressUpdated $e) {
        return $e->processedRows === 50 && $e->totalRows === 100;
    });
});

// ── SendNotificationJob ─────────────────────────────────────────

test('notification service dispatches email job', function (): void {
    Queue::fake();

    // Create a system email template
    \App\Models\NotificationTemplate::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'event_key' => 'invoice_sent',
        'channel' => 'email',
        'subject' => 'Invoice {invoice_number}',
        'body' => 'Your invoice {invoice_number} for {amount} has been sent.',
        'is_active' => true,
        'is_system' => false,
    ]);

    $service = app(NotificationService::class);
    $service->send(
        \App\Enums\NotificationEvent::INVOICE_SENT,
        $this->company,
        ['{invoice_number}' => 'INV-001', '{amount}' => '৳1,500.00'],
        [$this->user->id],
    );

    Queue::assertPushed(SendNotificationJob::class, function (SendNotificationJob $job) {
        return $job->toEmail === $this->user->email
            && str_contains($job->subject, 'INV-001');
    });
});

// ── ProcessImportJob ────────────────────────────────────────────

test('import service dispatches ProcessImportJob', function (): void {
    Queue::fake();

    $service = app(ImportService::class);
    $job = $service->queueImport(
        $this->company,
        'products',
        'imports/test.csv',
        $this->user,
        'test.csv',
    );

    expect($job->status)->toBe('pending');
    Queue::assertPushed(ProcessImportJob::class);
});

test('ProcessImportJob handles missing file gracefully', function (): void {
    $importJob = ImportJob::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'entity_type' => 'products',
        'file_path' => 'nonexistent/file.csv',
        'original_filename' => 'file.csv',
        'status' => 'pending',
        'created_by' => $this->user->id,
    ]);

    $job = new ProcessImportJob($importJob);
    $job->handle(app(ImportService::class));

    $importJob->refresh();
    expect($importJob->failed_rows)->toBe(1);
});

test('ProcessImportJob processes valid CSV rows', function (): void {
    // Create a test CSV
    $csvDir = storage_path('app/imports');
    if (! is_dir($csvDir)) {
        mkdir($csvDir, 0755, true);
    }

    $csvContent = "name,sku,selling_price,cost_price\nWidget A,WDG-001,150.00,100.00\nWidget B,WDG-002,200.00,120.00\n";
    file_put_contents("{$csvDir}/test-products.csv", $csvContent);

    $importJob = ImportJob::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'entity_type' => 'products',
        'file_path' => 'imports/test-products.csv',
        'original_filename' => 'test-products.csv',
        'status' => 'pending',
        'created_by' => $this->user->id,
    ]);

    Event::fake([ImportProgressUpdated::class]);

    $job = new ProcessImportJob($importJob);
    $job->handle(app(ImportService::class));

    $importJob->refresh();
    expect($importJob->status)->toBeIn(['completed', 'failed']);
    expect($importJob->total_rows)->toBe(2);

    // Cleanup
    unlink("{$csvDir}/test-products.csv");
});

// ── POSReceiptService ───────────────────────────────────────────

test('receipt service builds receipt data with paise to BDT conversion', function (): void {
    $service = app(POSReceiptService::class);

    $branch = \App\Models\Branch::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'name' => 'Main Branch',
        'code' => 'MB',
        'is_main' => true,
    ]);

    $session = \App\Models\POSSession::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'branch_id' => $branch->id,
        'user_id' => $this->user->id,
        'opened_by' => $this->user->id,
        'opened_at' => now(),
        'opening_balance' => 500000,
        'status' => 'open',
    ]);

    $sale = \App\Models\POSSale::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'branch_id' => $branch->id,
        'pos_session_id' => $session->id,
        'sale_number' => 'POS-0001',
        'sale_date' => now(),
        'subtotal' => 150000,
        'tax_amount' => 22500,
        'total_amount' => 172500,
        'payment_method' => 'cash',
        'amount_tendered' => 200000,
        'change_amount' => 27500,
        'status' => 'completed',
    ]);

    $data = $service->buildReceiptData($sale);

    expect($data['subtotal'])->toEqual(1500);
    expect($data['tax'])->toEqual(225);
    expect($data['total'])->toEqual(1725);
    expect($data['amount_tendered'])->toEqual(2000);
    expect($data['change'])->toEqual(275);
    expect($data['sale_number'])->toBe('POS-0001');
});
