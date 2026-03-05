<?php

use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Auth\Models\Company;
use App\Domain\Customer\Models\CreditNote;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\Invoice\Models\Invoice;
use App\Events\CreditNoteApplied;
use App\Listeners\CreateCreditNoteReversal;
use App\Support\Enums\CreditNoteStatus;
use App\Support\Enums\InvoiceStatus;
use App\Support\Enums\JournalEntryStatus;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('credit note application creates automatic journal reversal', function (): void {
    // Arrange - Don't fake events so the listener runs
    $company = Company::factory()->create();
    $user = \App\Domain\Auth\Models\User::factory()->create();
    $company->users()->attach($user, ['role' => 'member']);
    $this->actingAs($user);
    CompanyContext::setActive($company);
    
    // Create accounts for the journal entry
    $arAccount = \App\Domain\Accounting\Models\Account::factory()->create([
        'company_id' => $company->id,
        'code' => '1200',
        'name' => 'Accounts Receivable',
        'account_type' => 'asset',
    ]);
    $revenueAccount = \App\Domain\Accounting\Models\Account::factory()->create([
        'company_id' => $company->id,
        'code' => '4000',
        'name' => 'Sales Revenue',
        'account_type' => 'income',
    ]);
    
    // Create an invoice with a posted journal entry
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'status' => InvoiceStatus::SENT,
        'total_amount' => 100000, // $1000
        'amount_paid' => 0,
    ]);

    // Create the original journal entry (simulating ApproveInvoice)
    $originalJournal = JournalEntry::create([
        'company_id' => $company->id,
        'idempotency_key' => "invoice-{$invoice->id}",
        'entry_number' => 'JE-20260304-0001',
        'journal_code' => 'sales',
        'entry_date' => now()->toDateString(),
        'reference_type' => 'invoice',
        'reference_id' => $invoice->id,
        'description' => "Invoice {$invoice->invoice_number}",
        'currency' => 'BDT',
        'status' => JournalEntryStatus::POSTED,
        'is_system' => true,
        'posted_at' => now(),
    ]);
    
    // Add journal entry lines
    \App\Domain\Accounting\Models\JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $originalJournal->id,
        'account_id' => $arAccount->id,
        'line_no' => 1,
        'description' => 'AR for invoice',
        'debit' => 100000,
        'credit' => 0,
    ]);
    \App\Domain\Accounting\Models\JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $originalJournal->id,
        'account_id' => $revenueAccount->id,
        'line_no' => 2,
        'description' => 'Revenue from sale',
        'debit' => 0,
        'credit' => 100000,
    ]);

    // Create a credit note
    $paymentService = app(PaymentService::class);
    $creditNote = $paymentService->issueCreditNote($invoice, [
        'credit_date' => now()->toDateString(),
        'reason' => 'Damaged goods',
    ], [
        [
            'description' => 'Refund for damaged item',
            'quantity' => 1,
            'unit_price' => 50000, // $500
        ],
    ]);

    // Act - Apply the credit note (this should trigger the reversal)
    $paymentService->applyCreditNote($creditNote, $invoice);

    // Assert
    $creditNote->refresh();
    expect($creditNote->status)->toBe(CreditNoteStatus::APPLIED);

    // Check that the original journal entry has been marked as reversed
    $originalJournal->refresh();
    expect($originalJournal->reversed_by_id)->not->toBeNull();

    // Check that a reversal journal entry was created
    $reversal = JournalEntry::find($originalJournal->reversed_by_id);
    expect($reversal)->not->toBeNull();
    expect($reversal->reversal_of_id)->toBe($originalJournal->id);
    expect($reversal->description)->toContain('Credit Note');
    expect($reversal->description)->toContain($creditNote->credit_note_number);
    expect($reversal->idempotency_key)->toBe("credit-note-reversal-{$creditNote->id}-{$originalJournal->id}");
});

test('credit note reversal is idempotent', function (): void {
    // Arrange
    $company = Company::factory()->create();
    $user = \App\Domain\Auth\Models\User::factory()->create();
    $company->users()->attach($user, ['role' => 'member']);
    $this->actingAs($user);
    CompanyContext::setActive($company);
    
    // Create accounts for the journal entry
    $arAccount = \App\Domain\Accounting\Models\Account::factory()->create([
        'company_id' => $company->id,
        'code' => '1200',
        'name' => 'Accounts Receivable',
        'account_type' => 'asset',
    ]);
    $revenueAccount = \App\Domain\Accounting\Models\Account::factory()->create([
        'company_id' => $company->id,
        'code' => '4000',
        'name' => 'Sales Revenue',
        'account_type' => 'income',
    ]);
    
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'status' => InvoiceStatus::SENT,
        'total_amount' => 100000,
        'amount_paid' => 0,
    ]);

    $originalJournal = JournalEntry::create([
        'company_id' => $company->id,
        'idempotency_key' => "invoice-{$invoice->id}",
        'entry_number' => 'JE-20260304-0001',
        'journal_code' => 'sales',
        'entry_date' => now()->toDateString(),
        'reference_type' => 'invoice',
        'reference_id' => $invoice->id,
        'description' => "Invoice {$invoice->invoice_number}",
        'currency' => 'BDT',
        'status' => JournalEntryStatus::POSTED,
        'is_system' => true,
        'posted_at' => now(),
    ]);
    
    // Add journal entry lines
    \App\Domain\Accounting\Models\JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $originalJournal->id,
        'account_id' => $arAccount->id,
        'line_no' => 1,
        'description' => 'AR for invoice',
        'debit' => 100000,
        'credit' => 0,
    ]);
    \App\Domain\Accounting\Models\JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $originalJournal->id,
        'account_id' => $revenueAccount->id,
        'line_no' => 2,
        'description' => 'Revenue from sale',
        'debit' => 0,
        'credit' => 100000,
    ]);

    $paymentService = app(PaymentService::class);
    $creditNote = $paymentService->issueCreditNote($invoice, [
        'credit_date' => now()->toDateString(),
        'reason' => 'Damaged goods',
    ], [
        [
            'description' => 'Refund for damaged item',
            'quantity' => 1,
            'unit_price' => 50000,
        ],
    ]);

    // Act - Apply credit note twice
    $paymentService->applyCreditNote($creditNote, $invoice);
    $firstReversalId = $originalJournal->fresh()->reversed_by_id;

    // Simulate the listener being called again (shouldn't create duplicate)
    $listener = app(CreateCreditNoteReversal::class);
    $event = new CreditNoteApplied($creditNote, $invoice);
    $listener->handle($event);

    // Assert - No duplicate reversal created
    $originalJournal->refresh();
    expect($originalJournal->reversed_by_id)->toBe($firstReversalId);
    
    // Only one reversal should exist
    $reversalCount = JournalEntry::where('reversal_of_id', $originalJournal->id)->count();
    expect($reversalCount)->toBe(1);
});

test('credit note reversal handles missing original journal gracefully', function (): void {
    // Arrange
    $company = Company::factory()->create();
    $user = \App\Domain\Auth\Models\User::factory()->create();
    $company->users()->attach($user, ['role' => 'member']);
    $this->actingAs($user);
    CompanyContext::setActive($company);
    
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'status' => InvoiceStatus::DRAFT, // No journal entry for draft invoices
        'total_amount' => 100000,
        'amount_paid' => 0,
    ]);

    $paymentService = app(PaymentService::class);
    $creditNote = $paymentService->issueCreditNote($invoice, [
        'credit_date' => now()->toDateString(),
        'reason' => 'Damaged goods',
    ], [
        [
            'description' => 'Refund for damaged item',
            'quantity' => 1,
            'unit_price' => 50000,
        ],
    ]);

    // Act - Apply credit note (should not fail even without original journal)
    $paymentService->applyCreditNote($creditNote, $invoice);

    // Assert - Credit note is still applied successfully
    $creditNote->refresh();
    expect($creditNote->status)->toBe(CreditNoteStatus::APPLIED);
    
    // No reversal journal should be created
    $reversalCount = JournalEntry::where('description', 'like', '%Credit Note%')->count();
    expect($reversalCount)->toBe(0);
});