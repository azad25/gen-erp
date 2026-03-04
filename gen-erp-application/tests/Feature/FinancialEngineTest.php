<?php

use App\Domain\Accounting\DTOs\ProposedJournalEntry;
use App\Domain\Accounting\DTOs\ProposedJournalLine;
use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Accounting\Services\PostingService;
use App\Domain\Auth\Models\Company;
use App\Domain\Customer\Models\Customer;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\Invoice\Models\InvoiceItem;
use App\Domain\Inventory\Models\StockLevel;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\InventoryValuationService;
use App\Domain\Product\Models\Product;
use App\Domain\Sales\Actions\ApproveInvoice;
use App\Services\CompanyContext;
use App\Support\Enums\InvoiceStatus;
use App\Support\Enums\JournalCode;
use App\Support\Enums\JournalEntryStatus;
use App\Support\Enums\StockMovementType;

// ═══════════════════════════════════════════════════
// Phase 1: Financial Engine Tests — PostingService
// ═══════════════════════════════════════════════════

function seedPhase1SystemAccounts(int $companyId): void
{
    $accounts = [
        ['code' => '1001', 'name' => 'Cash in Hand', 'account_type' => 'asset', 'sub_type' => 'cash'],
        ['code' => '1002', 'name' => 'Cash at Bank', 'account_type' => 'asset', 'sub_type' => 'bank'],
        ['code' => '1003', 'name' => 'Accounts Receivable', 'account_type' => 'asset', 'sub_type' => 'receivable'],
        ['code' => '1004', 'name' => 'Inventory', 'account_type' => 'asset', 'sub_type' => 'inventory'],
        ['code' => '2001', 'name' => 'Accounts Payable', 'account_type' => 'liability', 'sub_type' => 'payable'],
        ['code' => '2002', 'name' => 'VAT Payable', 'account_type' => 'liability', 'sub_type' => 'current_liability'],
        ['code' => '2003', 'name' => 'TDS Payable', 'account_type' => 'liability', 'sub_type' => 'current_liability'],
        ['code' => '2004', 'name' => 'Salary Payable', 'account_type' => 'liability', 'sub_type' => 'current_liability'],
        ['code' => '3001', 'name' => 'Owner Capital', 'account_type' => 'equity', 'sub_type' => 'other'],
        ['code' => '4001', 'name' => 'Sales Revenue', 'account_type' => 'income', 'sub_type' => 'revenue'],
        ['code' => '5001', 'name' => 'COGS', 'account_type' => 'expense', 'sub_type' => 'cogs'],
        ['code' => '5002', 'name' => 'Salary Expense', 'account_type' => 'expense', 'sub_type' => 'operating_expense'],
        ['code' => '5005', 'name' => 'Other Expense', 'account_type' => 'expense', 'sub_type' => 'operating_expense'],
    ];

    foreach ($accounts as $a) {
        Account::withoutGlobalScopes()->create(array_merge($a, [
            'company_id' => $companyId,
            'is_system' => true,
            'is_active' => true,
        ]));
    }
}

function createProposedEntry(int $companyId, string $idempotencyKey, int $amount = 100000): ProposedJournalEntry
{
    $receivable = Account::withoutGlobalScopes()->where('company_id', $companyId)->where('code', '1003')->first();
    $revenue = Account::withoutGlobalScopes()->where('company_id', $companyId)->where('code', '4001')->first();

    return new ProposedJournalEntry(
        companyId: $companyId,
        idempotencyKey: $idempotencyKey,
        journalCode: JournalCode::SALES,
        entryDate: now()->toDateString(),
        description: 'Test entry',
        referenceType: 'test',
        referenceId: 1,
        lines: [
            new ProposedJournalLine(accountId: $receivable->id, debit: $amount, credit: 0, description: 'DR'),
            new ProposedJournalLine(accountId: $revenue->id, debit: 0, credit: $amount, description: 'CR'),
        ],
    );
}

// ───────────────────────────────────────────────────
// PostingService Tests
// ───────────────────────────────────────────────────

test('PostingService creates and posts a balanced journal entry', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedPhase1SystemAccounts($company->id);

    $posting = app(PostingService::class);
    $proposed = createProposedEntry($company->id, 'test_balanced_001');

    $entry = $posting->post($proposed);

    expect($entry)->toBeInstanceOf(JournalEntry::class);
    expect($entry->status)->toBe(JournalEntryStatus::POSTED);
    expect($entry->idempotency_key)->toBe('test_balanced_001');
    expect($entry->journal_code)->toBe(JournalCode::SALES);
    expect($entry->posted_at)->not->toBeNull();
    expect($entry->isBalanced())->toBeTrue();
    expect($entry->lines)->toHaveCount(2);
});

test('PostingService rejects unbalanced entries', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedPhase1SystemAccounts($company->id);

    $receivable = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '1003')->first();
    $revenue = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '4001')->first();

    $proposed = new ProposedJournalEntry(
        companyId: $company->id,
        idempotencyKey: 'test_unbalanced_001',
        journalCode: JournalCode::GENERAL,
        entryDate: now()->toDateString(),
        description: 'Unbalanced',
        referenceType: 'test',
        referenceId: 1,
        lines: [
            new ProposedJournalLine(accountId: $receivable->id, debit: 100000, credit: 0),
            new ProposedJournalLine(accountId: $revenue->id, debit: 0, credit: 50000),
        ],
    );

    app(PostingService::class)->post($proposed);
})->throws(InvalidArgumentException::class, 'not balanced');

test('PostingService idempotency: duplicate key returns existing entry', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedPhase1SystemAccounts($company->id);

    $posting = app(PostingService::class);
    $key = 'test_idempotent_001';

    $entry1 = $posting->post(createProposedEntry($company->id, $key));
    $entry2 = $posting->post(createProposedEntry($company->id, $key));

    expect($entry1->id)->toBe($entry2->id);
    expect(JournalEntry::withoutGlobalScopes()->where('idempotency_key', $key)->count())->toBe(1);
});

test('PostingService enforces lock date — cannot post before lock date', function (): void {
    $company = Company::factory()->create();
    $company->lock_date = now()->subDay();
    $company->save();
    CompanyContext::setActive($company);
    seedPhase1SystemAccounts($company->id);

    $receivable = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '1003')->first();
    $revenue = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '4001')->first();

    $proposed = new ProposedJournalEntry(
        companyId: $company->id,
        idempotencyKey: 'test_lockdate_001',
        journalCode: JournalCode::GENERAL,
        entryDate: now()->subDays(2)->toDateString(), // before lock_date
        description: 'Before lock date',
        referenceType: 'test',
        referenceId: 1,
        lines: [
            new ProposedJournalLine(accountId: $receivable->id, debit: 10000, credit: 0),
            new ProposedJournalLine(accountId: $revenue->id, debit: 0, credit: 10000),
        ],
    );

    app(PostingService::class)->post($proposed);
})->throws(RuntimeException::class, 'lock date');

test('PostingService allows posting after lock date', function (): void {
    $company = Company::factory()->create();
    $company->lock_date = now()->subDays(5);
    $company->save();
    CompanyContext::setActive($company);
    seedPhase1SystemAccounts($company->id);

    $posting = app(PostingService::class);
    $proposed = createProposedEntry($company->id, 'test_after_lock_001');

    $entry = $posting->post($proposed);

    expect($entry->status)->toBe(JournalEntryStatus::POSTED);
});

test('PostingService reverse() creates a swap debit/credit reversal entry', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedPhase1SystemAccounts($company->id);

    $posting = app(PostingService::class);
    $original = $posting->post(createProposedEntry($company->id, 'test_reverse_orig'));

    $reversal = $posting->reverse(
        original: $original,
        idempotencyKey: 'test_reverse_001',
        description: 'Reversal of test entry',
    );

    expect($reversal->id)->not->toBe($original->id);
    expect($reversal->status)->toBe(JournalEntryStatus::POSTED);
    expect($reversal->isBalanced())->toBeTrue();

    // Verify the debit/credit are swapped
    $originalLines = $original->lines->sortBy('line_no');
    $reversalLines = $reversal->lines->sortBy('line_no');

    foreach ($originalLines as $i => $origLine) {
        $revLine = $reversalLines->values()[$i];
        expect($revLine->debit)->toBe($origLine->credit);
        expect($revLine->credit)->toBe($origLine->debit);
    }
});

test('PostingService reverse() fails on draft entry', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedPhase1SystemAccounts($company->id);

    $service = app(\App\Domain\Accounting\Services\AccountingService::class);
    $cash = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '1001')->first();
    $revenue = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '4001')->first();

    $draft = $service->createEntry($company, [
        'entry_date' => now(),
        'description' => 'Draft entry',
    ], [
        ['account_id' => $cash->id, 'debit' => 10000, 'credit' => 0],
        ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 10000],
    ]);

    $posting = app(PostingService::class);
    $posting->reverse($draft, 'test_reverse_draft', 'Should fail');
})->throws(RuntimeException::class, 'Only posted entries');

test('PostingService sets line_no sequentially', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedPhase1SystemAccounts($company->id);

    $posting = app(PostingService::class);
    $entry = $posting->post(createProposedEntry($company->id, 'test_lineno_001'));

    $lineNumbers = $entry->lines->pluck('line_no')->sort()->values()->all();
    expect($lineNumbers)->toBe([1, 2]);
});

// ───────────────────────────────────────────────────
// ProposedJournalEntry DTO Tests
// ───────────────────────────────────────────────────

test('ProposedJournalEntry isBalanced() returns true when balanced', function (): void {
    $proposed = new ProposedJournalEntry(
        companyId: 1,
        idempotencyKey: 'dto_test_001',
        journalCode: JournalCode::GENERAL,
        entryDate: '2026-03-01',
        description: 'Test',
        referenceType: 'test',
        referenceId: 1,
        lines: [
            new ProposedJournalLine(accountId: 1, debit: 50000, credit: 0),
            new ProposedJournalLine(accountId: 2, debit: 0, credit: 50000),
        ],
    );

    expect($proposed->isBalanced())->toBeTrue();
    expect($proposed->totalDebits())->toBe(50000);
    expect($proposed->totalCredits())->toBe(50000);
});

test('ProposedJournalEntry isBalanced() returns false when unbalanced', function (): void {
    $proposed = new ProposedJournalEntry(
        companyId: 1,
        idempotencyKey: 'dto_test_002',
        journalCode: JournalCode::GENERAL,
        entryDate: '2026-03-01',
        description: 'Test',
        referenceType: 'test',
        referenceId: 1,
        lines: [
            new ProposedJournalLine(accountId: 1, debit: 50000, credit: 0),
            new ProposedJournalLine(accountId: 2, debit: 0, credit: 30000),
        ],
    );

    expect($proposed->isBalanced())->toBeFalse();
});

test('ProposedJournalEntry with zero amounts is not balanced', function (): void {
    $proposed = new ProposedJournalEntry(
        companyId: 1,
        idempotencyKey: 'dto_test_003',
        journalCode: JournalCode::GENERAL,
        entryDate: '2026-03-01',
        description: 'Test',
        referenceType: 'test',
        referenceId: 1,
        lines: [
            new ProposedJournalLine(accountId: 1, debit: 0, credit: 0),
            new ProposedJournalLine(accountId: 2, debit: 0, credit: 0),
        ],
    );

    expect($proposed->isBalanced())->toBeFalse();
});

// ───────────────────────────────────────────────────
// ApproveInvoice Action Tests
// ───────────────────────────────────────────────────

test('ApproveInvoice atomically deducts stock and posts journal', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedPhase1SystemAccounts($company->id);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Main Warehouse',
        'code' => 'WH-001',
    ]);
    $customer = Customer::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Test Customer',
    ]);
    $product = Product::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Widget',
        'slug' => 'widget',
        'sku' => 'WDG-001',
        'sale_price' => 50000,
        'track_inventory' => true,
    ]);

    // Seed opening stock
    StockLevel::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 100,
        'reserved' => 0,
    ]);

    $invoice = Invoice::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_number' => 'INV-TEST-001',
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'subtotal' => 500000,
        'tax_amount' => 75000, // 15% VAT
        'discount_amount' => 0,
        'total_amount' => 575000,
        'amount_paid' => 0,
        'status' => InvoiceStatus::DRAFT->value,
    ]);
    InvoiceItem::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'description' => 'Widget x10',
        'quantity' => 10,
        'unit' => 'pcs',
        'unit_price' => 50000,
        'line_total' => 500000,
    ]);

    $action = app(ApproveInvoice::class);
    $result = $action->execute($invoice);

    // Invoice is now SENT
    expect($result->status)->toBe(InvoiceStatus::SENT);
    expect($result->stock_deducted)->toBeTrue();

    // Stock was deducted
    $stockLevel = StockLevel::withoutGlobalScopes()
        ->where('warehouse_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->first();
    expect($stockLevel->quantity)->toBe(90.0); // 100 - 10

    // Journal entry was created with VAT separation
    $journalEntry = JournalEntry::withoutGlobalScopes()
        ->where('idempotency_key', "invoice_{$invoice->id}_approve")
        ->first();
    expect($journalEntry)->not->toBeNull();
    expect($journalEntry->status)->toBe(JournalEntryStatus::POSTED);
    expect($journalEntry->isBalanced())->toBeTrue();

    // Verify VAT is separate from revenue
    $lines = $journalEntry->lines;
    $vatLine = $lines->firstWhere('tax_code', 'OUTPUT_VAT');
    expect($vatLine)->not->toBeNull();
    expect($vatLine->credit)->toBe(75000);
});

test('ApproveInvoice is idempotent — second call does not create duplicate journal', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedPhase1SystemAccounts($company->id);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Warehouse',
        'code' => 'WH-002',
    ]);
    $customer = Customer::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Customer B',
    ]);

    $invoice = Invoice::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_number' => 'INV-TEST-002',
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'subtotal' => 100000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 100000,
        'amount_paid' => 0,
        'status' => InvoiceStatus::DRAFT->value,
    ]);

    $action = app(ApproveInvoice::class);
    $action->execute($invoice);

    // Second call should throw because invoice is no longer DRAFT
    $invoice->refresh();

    expect(fn () => $action->execute($invoice))->toThrow(RuntimeException::class, 'Only draft invoices');
});

test('ApproveInvoice rejects non-draft invoices', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Warehouse',
        'code' => 'WH-003',
    ]);
    $customer = Customer::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Customer C',
    ]);

    $invoice = Invoice::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_number' => 'INV-TEST-003',
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'subtotal' => 100000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 100000,
        'amount_paid' => 0,
        'status' => InvoiceStatus::SENT->value, // Already sent
    ]);

    $action = app(ApproveInvoice::class);
    $action->execute($invoice);
})->throws(RuntimeException::class, 'Only draft invoices');

// ───────────────────────────────────────────────────
// VAT Separation Tests
// ───────────────────────────────────────────────────

test('Invoice journal entry separates VAT from revenue correctly', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedPhase1SystemAccounts($company->id);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'VAT Warehouse',
        'code' => 'WH-VAT',
    ]);
    $customer = Customer::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'VAT Customer',
    ]);

    $invoice = Invoice::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_number' => 'INV-VAT-001',
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'subtotal' => 1000000, // ৳10,000
        'tax_amount' => 150000, // ৳1,500 (15%)
        'discount_amount' => 0,
        'total_amount' => 1150000, // ৳11,500
        'amount_paid' => 0,
        'status' => InvoiceStatus::DRAFT->value,
    ]);

    $action = app(ApproveInvoice::class);
    $action->execute($invoice);

    $entry = JournalEntry::withoutGlobalScopes()
        ->where('idempotency_key', "invoice_{$invoice->id}_approve")
        ->with('lines.account')
        ->first();

    $lines = $entry->lines;

    // DR: Receivable = total_amount
    $drReceivable = $lines->first(fn ($l) => $l->debit > 0 && $l->account->sub_type->value === 'receivable');
    expect($drReceivable->debit)->toBe(1150000);

    // CR: Revenue = subtotal (NOT total_amount)
    $crRevenue = $lines->first(fn ($l) => $l->credit > 0 && $l->account->sub_type->value === 'revenue');
    expect($crRevenue->credit)->toBe(1000000);

    // CR: VAT Payable = tax_amount
    $crVat = $lines->first(fn ($l) => $l->credit > 0 && $l->tax_code === 'OUTPUT_VAT');
    expect($crVat->credit)->toBe(150000);
    expect($crVat->tax_rate)->toBe(1500); // 15% = 1500 basis points
    expect($crVat->tax_base_amount)->toBe(1000000);
});

// ───────────────────────────────────────────────────
// Multi-tenancy Isolation Tests
// ───────────────────────────────────────────────────

test('PostingService isolates journal entries between companies', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    CompanyContext::setActive($companyA);
    seedPhase1SystemAccounts($companyA->id);

    CompanyContext::setActive($companyB);
    seedPhase1SystemAccounts($companyB->id);

    $posting = app(PostingService::class);

    // Post to company A
    $entryA = $posting->post(createProposedEntry($companyA->id, 'tenant_iso_A'));

    // Post to company B
    $entryB = $posting->post(createProposedEntry($companyB->id, 'tenant_iso_B'));

    // Company A should not see company B entries
    CompanyContext::setActive($companyA);
    expect(JournalEntry::count())->toBe(1);
    expect(JournalEntry::first()->id)->toBe($entryA->id);

    CompanyContext::setActive($companyB);
    expect(JournalEntry::count())->toBe(1);
    expect(JournalEntry::first()->id)->toBe($entryB->id);
});

// ───────────────────────────────────────────────────
// Integrity Check Command Tests
// ───────────────────────────────────────────────────

test('integrity:check command passes on clean database', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedPhase1SystemAccounts($company->id);

    // Post a balanced entry
    $posting = app(PostingService::class);
    $posting->post(createProposedEntry($company->id, 'integrity_clean_001'));

    $this->artisan('integrity:check', ['--company' => $company->id])
        ->assertExitCode(0);
});

// ───────────────────────────────────────────────────
// COGS Integration Tests (Phase 2)
// ───────────────────────────────────────────────────

test('ApproveInvoice posts COGS journal lines when stock layers exist', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedPhase1SystemAccounts($company->id);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'COGS Warehouse',
        'code' => 'WH-COGS',
    ]);
    $customer = Customer::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'COGS Customer',
    ]);
    $product = Product::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'COGS Widget',
        'slug' => 'cogs-widget',
        'sku' => 'COGS-001',
        'sale_price' => 50000,
        'track_inventory' => true,
    ]);

    // Create stock level + stock layer (purchase at ৳200/unit)
    StockLevel::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 100,
        'reserved' => 0,
    ]);
    $purchaseMovement = StockMovement::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'movement_type' => StockMovementType::PURCHASE_RECEIPT,
        'quantity' => 100,
        'quantity_before' => 0,
        'quantity_after' => 100,
        'unit_cost' => 20000, // ৳200 per unit
        'movement_date' => now()->subDays(10)->toDateString(),
    ]);
    app(InventoryValuationService::class)->createLayer($purchaseMovement);

    // Create invoice: sell 10 units at ৳500 each
    $invoice = Invoice::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_number' => 'INV-COGS-001',
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'subtotal' => 500000, // 10 x ৳500
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 500000,
        'amount_paid' => 0,
        'status' => InvoiceStatus::DRAFT->value,
    ]);
    InvoiceItem::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'description' => 'COGS Widget x10',
        'quantity' => 10,
        'unit' => 'pcs',
        'unit_price' => 50000,
        'line_total' => 500000,
    ]);

    // Approve the invoice
    $action = app(ApproveInvoice::class);
    $result = $action->execute($invoice);

    expect($result->status)->toBe(InvoiceStatus::SENT);

    // Verify journal has COGS lines
    $entry = JournalEntry::withoutGlobalScopes()
        ->where('idempotency_key', "invoice_{$invoice->id}_approve")
        ->with('lines.account')
        ->first();

    $lines = $entry->lines;

    // Revenue side: DR Receivable 500000, CR Revenue 500000
    $drReceivable = $lines->first(fn ($l) => $l->debit > 0 && $l->account->sub_type->value === 'receivable');
    expect($drReceivable->debit)->toBe(500000);

    $crRevenue = $lines->first(fn ($l) => $l->credit > 0 && $l->account->sub_type->value === 'revenue');
    expect($crRevenue->credit)->toBe(500000);

    // COGS side: DR COGS 200000 (10 x ৳200), CR Inventory 200000
    $drCogs = $lines->first(fn ($l) => $l->debit > 0 && $l->account->sub_type->value === 'cogs');
    expect($drCogs)->not->toBeNull();
    expect($drCogs->debit)->toBe(200000); // 10 units * 20000 paise

    $crInventory = $lines->first(fn ($l) => $l->credit > 0 && $l->account->sub_type->value === 'inventory');
    expect($crInventory)->not->toBeNull();
    expect($crInventory->credit)->toBe(200000);

    // Entry is still balanced: DR = 500000 + 200000, CR = 500000 + 200000
    expect($entry->isBalanced())->toBeTrue();
});

test('Zero-VAT invoice has no VAT line in journal', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedPhase1SystemAccounts($company->id);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'No VAT Warehouse',
        'code' => 'WH-NOVAT',
    ]);
    $customer = Customer::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'No VAT Customer',
    ]);

    $invoice = Invoice::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_number' => 'INV-NOVAT-001',
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'subtotal' => 200000,
        'tax_amount' => 0, // zero VAT
        'discount_amount' => 0,
        'total_amount' => 200000,
        'amount_paid' => 0,
        'status' => InvoiceStatus::DRAFT->value,
    ]);

    $action = app(ApproveInvoice::class);
    $action->execute($invoice);

    $entry = JournalEntry::withoutGlobalScopes()
        ->where('idempotency_key', "invoice_{$invoice->id}_approve")
        ->with('lines')
        ->first();

    // Should only have 2 lines (DR Receivable, CR Revenue), no VAT line
    $vatLine = $entry->lines->firstWhere('tax_code', 'OUTPUT_VAT');
    expect($vatLine)->toBeNull();
    expect($entry->lines)->toHaveCount(2);
    expect($entry->isBalanced())->toBeTrue();
});

// ───────────────────────────────────────────────────
// VAT Report Query Tests
// ───────────────────────────────────────────────────

test('VAT report query correctly sums OUTPUT_VAT and INPUT_VAT', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedPhase1SystemAccounts($company->id);

    $posting = app(PostingService::class);
    $receivable = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '1003')->first();
    $revenue = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '4001')->first();
    $vatPayable = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '2002')->first();
    $inventory = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '1004')->first();
    $payable = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '2001')->first();

    // Post a sales invoice with OUTPUT_VAT
    $posting->post(new ProposedJournalEntry(
        companyId: $company->id,
        idempotencyKey: 'vat_report_sale_001',
        journalCode: JournalCode::SALES,
        entryDate: now()->toDateString(),
        description: 'Sale with VAT',
        referenceType: 'test',
        referenceId: 1,
        lines: [
            new ProposedJournalLine(accountId: $receivable->id, debit: 115000, credit: 0, description: 'AR'),
            new ProposedJournalLine(accountId: $revenue->id, debit: 0, credit: 100000, description: 'Revenue'),
            new ProposedJournalLine(
                accountId: $vatPayable->id, debit: 0, credit: 15000,
                description: 'Output VAT',
                taxCode: 'OUTPUT_VAT', taxRate: 1500, taxBaseAmount: 100000,
            ),
        ],
    ));

    // Post a purchase with INPUT_VAT
    $posting->post(new ProposedJournalEntry(
        companyId: $company->id,
        idempotencyKey: 'vat_report_purchase_001',
        journalCode: JournalCode::PURCHASE,
        entryDate: now()->toDateString(),
        description: 'Purchase with VAT',
        referenceType: 'test',
        referenceId: 2,
        lines: [
            new ProposedJournalLine(accountId: $inventory->id, debit: 50000, credit: 0, description: 'Inventory'),
            new ProposedJournalLine(
                accountId: $vatPayable->id, debit: 7500, credit: 0,
                description: 'Input VAT',
                taxCode: 'INPUT_VAT', taxRate: 1500, taxBaseAmount: 50000,
            ),
            new ProposedJournalLine(accountId: $payable->id, debit: 0, credit: 57500, description: 'AP'),
        ],
    ));

    // Query: sum OUTPUT_VAT credits vs INPUT_VAT debits
    $outputVat = \App\Domain\Accounting\Models\JournalEntryLine::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->where('tax_code', 'OUTPUT_VAT')
        ->sum('credit');

    $inputVat = \App\Domain\Accounting\Models\JournalEntryLine::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->where('tax_code', 'INPUT_VAT')
        ->sum('debit');

    expect((int) $outputVat)->toBe(15000);
    expect((int) $inputVat)->toBe(7500);

    // Net VAT payable = OUTPUT - INPUT = 7500
    $netVatPayable = (int) $outputVat - (int) $inputVat;
    expect($netVatPayable)->toBe(7500);
});

// ───────────────────────────────────────────────────
// Phase 3: Cancel Invoice with Reversal Tests
// ───────────────────────────────────────────────────

test('CancelInvoiceWithReversal reverses journal and restores stock', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedPhase1SystemAccounts($company->id);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Cancel Warehouse',
        'code' => 'WH-CANCEL',
    ]);
    $customer = Customer::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Cancel Customer',
    ]);
    $product = Product::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Cancel Widget',
        'slug' => 'cancel-widget',
        'sku' => 'CANCEL-001',
        'sale_price' => 30000,
        'track_inventory' => true,
    ]);

    StockLevel::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 50,
        'reserved' => 0,
    ]);

    $invoice = Invoice::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_number' => 'INV-CANCEL-001',
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'subtotal' => 150000, // 5 x ৳300
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 150000,
        'amount_paid' => 0,
        'status' => InvoiceStatus::DRAFT->value,
    ]);
    InvoiceItem::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'description' => 'Cancel Widget x5',
        'quantity' => 5,
        'unit' => 'pcs',
        'unit_price' => 30000,
        'line_total' => 150000,
    ]);

    // Step 1: Approve the invoice (stock: 50 → 45)
    $approveAction = app(ApproveInvoice::class);
    $invoice = $approveAction->execute($invoice);
    expect($invoice->status)->toBe(InvoiceStatus::SENT);

    $stockAfterApprove = StockLevel::withoutGlobalScopes()
        ->where('warehouse_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->first();
    expect($stockAfterApprove->quantity)->toBe(45.0);

    // Step 2: Cancel the invoice (stock: 45 → 50, journal reversed)
    $cancelAction = app(\App\Domain\Sales\Actions\CancelInvoiceWithReversal::class);
    $invoice = $cancelAction->execute($invoice, reason: 'Customer changed mind');

    expect($invoice->status)->toBe(InvoiceStatus::CANCELLED);
    expect($invoice->stock_deducted)->toBeFalse();

    // Stock restored
    $stockAfterCancel = StockLevel::withoutGlobalScopes()
        ->where('warehouse_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->first();
    expect($stockAfterCancel->quantity)->toBe(50.0);

    // Two journal entries: original + reversal, both balanced
    $entries = JournalEntry::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->where('reference_type', 'invoice')
        ->where('reference_id', $invoice->id)
        ->get();
    // Original approve entry
    expect($entries->count())->toBeGreaterThanOrEqual(1);

    // Reversal entry
    $reversal = JournalEntry::withoutGlobalScopes()
        ->where('idempotency_key', "invoice_{$invoice->id}_cancel_reversal")
        ->first();
    expect($reversal)->not->toBeNull();
    expect($reversal->status)->toBe(JournalEntryStatus::POSTED);
    expect($reversal->isBalanced())->toBeTrue();
});

test('CancelInvoiceWithReversal rejects draft invoices', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'CancelDraft Warehouse',
        'code' => 'WH-CD',
    ]);
    $customer = Customer::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'CancelDraft Customer',
    ]);

    $invoice = Invoice::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_number' => 'INV-CD-001',
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'subtotal' => 100000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 100000,
        'amount_paid' => 0,
        'status' => InvoiceStatus::DRAFT->value, // Not sent
    ]);

    $action = app(\App\Domain\Sales\Actions\CancelInvoiceWithReversal::class);
    $action->execute($invoice);
})->throws(RuntimeException::class, 'Only sent or overdue');

// ───────────────────────────────────────────────────
// Phase 3: Month-End Close Tests
// ───────────────────────────────────────────────────

test('MonthEndClose sets lock_date after passing integrity check', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    seedPhase1SystemAccounts($company->id);

    // Post a clean entry so integrity check passes
    $posting = app(PostingService::class);
    $posting->post(createProposedEntry($company->id, 'monthend_test_001'));

    $closeAction = new \App\Domain\Accounting\Actions\MonthEndClose();
    $closingDate = now()->subDay()->toDateString();
    $result = $closeAction->execute($company, $closingDate);

    // Check that the company's lock_date was updated
    $company->refresh();
    expect($company->lock_date->toDateString())->toBe($closingDate);
    
    // Check the result array
    expect($result['integrity_check_passed'])->toBeTrue();
    expect($result)->toHaveKey('invoices_checked');
    expect($result)->toHaveKey('journal_entries_checked');

    // Now posting before lock_date should fail
    $receivable = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '1003')->first();
    $revenue = Account::withoutGlobalScopes()->where('company_id', $company->id)->where('code', '4001')->first();

    $proposed = new ProposedJournalEntry(
        companyId: $company->id,
        idempotencyKey: 'monthend_locked_001',
        journalCode: JournalCode::GENERAL,
        entryDate: now()->subDays(3)->toDateString(),
        description: 'Should fail',
        referenceType: 'test',
        referenceId: 99,
        lines: [
            new ProposedJournalLine(accountId: $receivable->id, debit: 10000, credit: 0),
            new ProposedJournalLine(accountId: $revenue->id, debit: 0, credit: 10000),
        ],
    );

    expect(fn () => $posting->post($proposed))->toThrow(RuntimeException::class, 'lock date');
});

test('MonthEndClose rejects closing date before current lock_date', function (): void {
    $company = Company::factory()->create(['lock_date' => '2026-02-28']);
    CompanyContext::setActive($company);

    $closeAction = new \App\Domain\Accounting\Actions\MonthEndClose();
    $closeAction->execute($company, '2026-02-15'); // Before current lock
})->throws(RuntimeException::class, 'must be after');
