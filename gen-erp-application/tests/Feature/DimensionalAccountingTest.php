<?php

use App\Domain\Accounting\DTOs\ProposedJournalEntry;
use App\Domain\Accounting\DTOs\ProposedJournalLine;
use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\CostCenter;
use App\Domain\Accounting\Services\PostingService;
use App\Domain\Auth\Models\Company;
use App\Support\Enums\JournalCode;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('journal entry lines can store custom dimensions', function (): void {
    // Arrange
    $company = Company::factory()->create();
    $this->actingAs($company->users()->first());

    $receivableAccount = Account::factory()->create([
        'company_id' => $company->id,
        'account_code' => '1200',
        'account_name' => 'Accounts Receivable',
    ]);

    $revenueAccount = Account::factory()->create([
        'company_id' => $company->id,
        'account_code' => '4000',
        'account_name' => 'Sales Revenue',
    ]);

    $costCenter = CostCenter::factory()->create([
        'company_id' => $company->id,
        'name' => 'Marketing Department',
        'code' => 'MKT',
    ]);

    // Custom dimensions for this transaction
    $customDimensions = [
        'project_id' => 'PRJ-2024-001',
        'campaign_id' => 'CAMP-SPRING-2024',
        'region' => 'North America',
        'sales_rep_id' => 'REP-001',
    ];

    $postingService = app(PostingService::class);

    // Act - Create journal entry with custom dimensions
    $proposed = new ProposedJournalEntry(
        companyId: $company->id,
        idempotencyKey: 'test-dimensions-' . uniqid(),
        journalCode: JournalCode::SALES,
        entryDate: now()->toDateString(),
        description: 'Sale with custom dimensions',
        referenceType: 'invoice',
        referenceId: 12345,
        lines: [
            new ProposedJournalLine(
                accountId: $receivableAccount->id,
                debit: 120000, // $1200
                credit: 0,
                description: 'Customer payment due',
                branchId: null,
                costCenterId: $costCenter->id,
                dimensions: $customDimensions,
            ),
            new ProposedJournalLine(
                accountId: $revenueAccount->id,
                debit: 0,
                credit: 120000,
                description: 'Sales revenue',
                branchId: null,
                costCenterId: $costCenter->id,
                dimensions: $customDimensions,
            ),
        ],
        currency: 'USD',
    );

    $journalEntry = $postingService->post($proposed);

    // Assert
    expect($journalEntry->lines)->toHaveCount(2);

    $receivableLine = $journalEntry->lines->where('account_id', $receivableAccount->id)->first();
    $revenueLine = $journalEntry->lines->where('account_id', $revenueAccount->id)->first();

    // Check that dimensions are stored correctly
    expect($receivableLine->dimensions)->toBe($customDimensions);
    expect($revenueLine->dimensions)->toBe($customDimensions);

    // Check that cost center is also stored
    expect($receivableLine->cost_center_id)->toBe($costCenter->id);
    expect($revenueLine->cost_center_id)->toBe($costCenter->id);

    // Verify we can query by dimensions
    $projectLines = \App\Domain\Accounting\Models\JournalEntryLine::where('company_id', $company->id)
        ->whereJsonContains('dimensions->project_id', 'PRJ-2024-001')
        ->get();

    expect($projectLines)->toHaveCount(2);

    $campaignLines = \App\Domain\Accounting\Models\JournalEntryLine::where('company_id', $company->id)
        ->whereJsonContains('dimensions->campaign_id', 'CAMP-SPRING-2024')
        ->get();

    expect($campaignLines)->toHaveCount(2);
});

test('dimensions are preserved in journal entry reversals', function (): void {
    // Arrange
    $company = Company::factory()->create();
    $this->actingAs($company->users()->first());

    $account1 = Account::factory()->create(['company_id' => $company->id]);
    $account2 = Account::factory()->create(['company_id' => $company->id]);

    $originalDimensions = [
        'project_id' => 'PRJ-ORIGINAL',
        'department' => 'Sales',
    ];

    $postingService = app(PostingService::class);

    // Create original entry with dimensions
    $original = new ProposedJournalEntry(
        companyId: $company->id,
        idempotencyKey: 'original-' . uniqid(),
        journalCode: JournalCode::GENERAL,
        entryDate: now()->toDateString(),
        description: 'Original entry with dimensions',
        referenceType: 'test',
        referenceId: 1,
        lines: [
            new ProposedJournalLine(
                accountId: $account1->id,
                debit: 50000,
                credit: 0,
                description: 'Debit line',
                dimensions: $originalDimensions,
            ),
            new ProposedJournalLine(
                accountId: $account2->id,
                debit: 0,
                credit: 50000,
                description: 'Credit line',
                dimensions: $originalDimensions,
            ),
        ],
    );

    $originalEntry = $postingService->post($original);

    // Act - Create reversal
    $reversal = $postingService->reverse(
        original: $originalEntry,
        idempotencyKey: 'reversal-' . uniqid(),
        description: 'Reversal of original entry',
    );

    // Assert - Dimensions are preserved in reversal
    expect($reversal->lines)->toHaveCount(2);

    foreach ($reversal->lines as $line) {
        expect($line->dimensions)->toBe($originalDimensions);
    }

    // Verify the reversal swapped debits and credits but kept dimensions
    $reversalDebitLine = $reversal->lines->where('debit', '>', 0)->first();
    $reversalCreditLine = $reversal->lines->where('credit', '>', 0)->first();

    expect($reversalDebitLine->account_id)->toBe($account2->id); // Originally credit account
    expect($reversalCreditLine->account_id)->toBe($account1->id); // Originally debit account
    expect($reversalDebitLine->dimensions)->toBe($originalDimensions);
    expect($reversalCreditLine->dimensions)->toBe($originalDimensions);
});

test('can query journal lines by multiple dimension criteria', function (): void {
    // Arrange
    $company = Company::factory()->create();
    $this->actingAs($company->users()->first());

    $account = Account::factory()->create(['company_id' => $company->id]);
    $postingService = app(PostingService::class);

    // Create entries with different dimension combinations
    $entries = [
        [
            'dimensions' => ['project_id' => 'PRJ-001', 'region' => 'US', 'department' => 'Sales'],
            'amount' => 10000,
        ],
        [
            'dimensions' => ['project_id' => 'PRJ-001', 'region' => 'EU', 'department' => 'Sales'],
            'amount' => 20000,
        ],
        [
            'dimensions' => ['project_id' => 'PRJ-002', 'region' => 'US', 'department' => 'Marketing'],
            'amount' => 30000,
        ],
    ];

    foreach ($entries as $i => $entryData) {
        $proposed = new ProposedJournalEntry(
            companyId: $company->id,
            idempotencyKey: "test-query-{$i}-" . uniqid(),
            journalCode: JournalCode::GENERAL,
            entryDate: now()->toDateString(),
            description: "Test entry {$i}",
            referenceType: 'test',
            referenceId: $i,
            lines: [
                new ProposedJournalLine(
                    accountId: $account->id,
                    debit: $entryData['amount'],
                    credit: 0,
                    description: "Test line {$i}",
                    dimensions: $entryData['dimensions'],
                ),
                new ProposedJournalLine(
                    accountId: $account->id,
                    debit: 0,
                    credit: $entryData['amount'],
                    description: "Balancing line {$i}",
                    dimensions: $entryData['dimensions'],
                ),
            ],
        );

        $postingService->post($proposed);
    }

    // Act & Assert - Query by single dimension
    $project001Lines = \App\Domain\Accounting\Models\JournalEntryLine::where('company_id', $company->id)
        ->whereJsonContains('dimensions->project_id', 'PRJ-001')
        ->get();
    expect($project001Lines)->toHaveCount(4); // 2 entries × 2 lines each

    // Query by multiple dimensions (AND condition)
    $usLinesProject001 = \App\Domain\Accounting\Models\JournalEntryLine::where('company_id', $company->id)
        ->whereJsonContains('dimensions->project_id', 'PRJ-001')
        ->whereJsonContains('dimensions->region', 'US')
        ->get();
    expect($usLinesProject001)->toHaveCount(2); // 1 entry × 2 lines

    // Query by department
    $salesLines = \App\Domain\Accounting\Models\JournalEntryLine::where('company_id', $company->id)
        ->whereJsonContains('dimensions->department', 'Sales')
        ->get();
    expect($salesLines)->toHaveCount(4); // 2 entries × 2 lines each

    // Verify amounts can be summed by dimension
    $project001Total = \App\Domain\Accounting\Models\JournalEntryLine::where('company_id', $company->id)
        ->whereJsonContains('dimensions->project_id', 'PRJ-001')
        ->where('debit', '>', 0)
        ->sum('debit');
    expect($project001Total)->toBe(30000); // 10000 + 20000
});