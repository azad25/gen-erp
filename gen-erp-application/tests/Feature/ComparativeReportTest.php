<?php

namespace Tests\Feature;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Accounting\Models\JournalEntryLine;
use App\Domain\Auth\Models\Company;
use App\Domain\Report\Services\ComparativeReportService;
use App\Support\Enums\AccountType;
use App\Support\Enums\JournalEntryStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComparativeReportTest extends TestCase
{
    use RefreshDatabase;

    private ComparativeReportService $comparativeReportService;
    private Company $company;
    private Account $revenueAccount;
    private Account $expenseAccount;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->comparativeReportService = app(ComparativeReportService::class);
        
        $this->company = Company::factory()->create();
        
        // Create test accounts
        $this->revenueAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type' => AccountType::INCOME,
            'code' => '4000',
            'name' => 'Sales Revenue',
        ]);
        
        $this->expenseAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type' => AccountType::EXPENSE,
            'code' => '5000',
            'name' => 'Cost of Goods Sold',
        ]);
    }

    /** @test */
    public function it_generates_year_over_year_profit_and_loss_comparison()
    {
        // Create current year data (2024)
        $currentYear = Carbon::create(2024, 6, 15);
        $this->createJournalEntry($currentYear, $this->revenueAccount, 0, 100000); // Revenue
        $this->createJournalEntry($currentYear, $this->expenseAccount, 60000, 0);  // Expense

        // Create previous year data (2023)
        $previousYear = Carbon::create(2023, 6, 15);
        $this->createJournalEntry($previousYear, $this->revenueAccount, 0, 80000);  // Revenue
        $this->createJournalEntry($previousYear, $this->expenseAccount, 50000, 0); // Expense

        // Generate YoY comparison
        $result = $this->comparativeReportService->yearOverYearProfitAndLoss(
            $this->company,
            Carbon::create(2024, 1, 1),
            Carbon::create(2024, 12, 31)
        );

        // Assertions
        $this->assertEquals('Year-over-Year', $result['comparison_type']);
        
        // Current period
        $this->assertEquals(100000, $result['current_period']['revenue']);
        $this->assertEquals(60000, $result['current_period']['expenses']);
        $this->assertEquals(40000, $result['current_period']['net_income']);
        
        // Previous period
        $this->assertEquals(80000, $result['previous_period']['revenue']);
        $this->assertEquals(50000, $result['previous_period']['expenses']);
        $this->assertEquals(30000, $result['previous_period']['net_income']);
        
        // Variance calculations
        $this->assertEquals(20000, $result['variance']['revenue']['amount']);
        $this->assertEquals(25.0, $result['variance']['revenue']['percentage']);
        $this->assertEquals('increase', $result['variance']['revenue']['direction']);
        
        $this->assertEquals(10000, $result['variance']['expenses']['amount']);
        $this->assertEquals(20.0, $result['variance']['expenses']['percentage']);
        
        $this->assertEquals(10000, $result['variance']['net_income']['amount']);
        $this->assertEquals(33.33, $result['variance']['net_income']['percentage']);
    }

    /** @test */
    public function it_generates_month_over_month_comparison()
    {
        // Create current month data (June 2024)
        $currentMonth = Carbon::create(2024, 6, 15);
        $this->createJournalEntry($currentMonth, $this->revenueAccount, 0, 50000);
        $this->createJournalEntry($currentMonth, $this->expenseAccount, 30000, 0);

        // Create previous month data (May 2024)
        $previousMonth = Carbon::create(2024, 5, 15);
        $this->createJournalEntry($previousMonth, $this->revenueAccount, 0, 45000);
        $this->createJournalEntry($previousMonth, $this->expenseAccount, 28000, 0);

        $result = $this->comparativeReportService->monthOverMonthProfitAndLoss(
            $this->company,
            Carbon::create(2024, 6, 1),
            Carbon::create(2024, 6, 30)
        );

        $this->assertEquals('Month-over-Month', $result['comparison_type']);
        $this->assertEquals(50000, $result['current_period']['revenue']);
        $this->assertEquals(45000, $result['previous_period']['revenue']);
        $this->assertEquals(5000, $result['variance']['revenue']['amount']);
    }

    /** @test */
    public function it_generates_quarter_over_quarter_comparison()
    {
        // Create Q2 2024 data
        $q2Date = Carbon::create(2024, 5, 15);
        $this->createJournalEntry($q2Date, $this->revenueAccount, 0, 150000);
        $this->createJournalEntry($q2Date, $this->expenseAccount, 90000, 0);

        // Create Q1 2024 data
        $q1Date = Carbon::create(2024, 2, 15);
        $this->createJournalEntry($q1Date, $this->revenueAccount, 0, 120000);
        $this->createJournalEntry($q1Date, $this->expenseAccount, 75000, 0);

        $result = $this->comparativeReportService->quarterOverQuarterProfitAndLoss(
            $this->company,
            Carbon::create(2024, 4, 1),
            Carbon::create(2024, 6, 30)
        );

        $this->assertEquals('Quarter-over-Quarter', $result['comparison_type']);
        $this->assertEquals(150000, $result['current_period']['revenue']);
        $this->assertEquals(120000, $result['previous_period']['revenue']);
        $this->assertEquals(30000, $result['variance']['revenue']['amount']);
        $this->assertEquals(25.0, $result['variance']['revenue']['percentage']);
    }

    /** @test */
    public function it_generates_year_over_year_balance_sheet_comparison()
    {
        $assetAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type' => AccountType::ASSET,
            'code' => '1000',
            'name' => 'Cash',
        ]);

        $liabilityAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type' => AccountType::LIABILITY,
            'code' => '2000',
            'name' => 'Accounts Payable',
        ]);

        // Create current year balance sheet data
        $currentDate = Carbon::create(2024, 12, 31);
        $this->createJournalEntry($currentDate, $assetAccount, 100000, 0);
        $this->createJournalEntry($currentDate, $liabilityAccount, 0, 30000);

        // Create previous year balance sheet data
        $previousDate = Carbon::create(2023, 12, 31);
        $this->createJournalEntry($previousDate, $assetAccount, 80000, 0);
        $this->createJournalEntry($previousDate, $liabilityAccount, 0, 25000);

        $result = $this->comparativeReportService->yearOverYearBalanceSheet(
            $this->company,
            $currentDate
        );

        $this->assertEquals('Year-over-Year Balance Sheet', $result['comparison_type']);
        $this->assertArrayHasKey('current_period', $result);
        $this->assertArrayHasKey('previous_period', $result);
        $this->assertArrayHasKey('variance', $result);
    }

    /** @test */
    public function it_generates_trend_analysis_over_multiple_periods()
    {
        // Create data for 6 months
        $months = [
            Carbon::create(2024, 1, 15) => ['revenue' => 80000, 'expense' => 50000],
            Carbon::create(2024, 2, 15) => ['revenue' => 85000, 'expense' => 52000],
            Carbon::create(2024, 3, 15) => ['revenue' => 90000, 'expense' => 55000],
            Carbon::create(2024, 4, 15) => ['revenue' => 95000, 'expense' => 58000],
            Carbon::create(2024, 5, 15) => ['revenue' => 100000, 'expense' => 60000],
            Carbon::create(2024, 6, 15) => ['revenue' => 105000, 'expense' => 62000],
        ];

        foreach ($months as $date => $amounts) {
            $this->createJournalEntry($date, $this->revenueAccount, 0, $amounts['revenue']);
            $this->createJournalEntry($date, $this->expenseAccount, $amounts['expense'], 0);
        }

        $result = $this->comparativeReportService->trendAnalysis(
            $this->company,
            Carbon::create(2024, 6, 30),
            6,
            'month'
        );

        $this->assertEquals('month', $result['period_type']);
        $this->assertEquals(6, $result['periods_analyzed']);
        $this->assertCount(6, $result['trends']);
        
        // Check that trends are in chronological order (oldest to newest)
        $this->assertEquals('Jan 2024', $result['trends'][0]['period']);
        $this->assertEquals('Jun 2024', $result['trends'][5]['period']);
        
        // Check summary statistics
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('revenue', $result['summary']);
        $this->assertArrayHasKey('growth_rate', $result['summary']['revenue']);
    }

    /** @test */
    public function it_handles_zero_previous_amounts_in_variance_calculation()
    {
        // Create current year data only (no previous year data)
        $currentYear = Carbon::create(2024, 6, 15);
        $this->createJournalEntry($currentYear, $this->revenueAccount, 0, 100000);
        $this->createJournalEntry($currentYear, $this->expenseAccount, 60000, 0);

        $result = $this->comparativeReportService->yearOverYearProfitAndLoss(
            $this->company,
            Carbon::create(2024, 1, 1),
            Carbon::create(2024, 12, 31)
        );

        // When previous amount is 0, percentage should be 0
        $this->assertEquals(100000, $result['variance']['revenue']['amount']);
        $this->assertEquals(0, $result['variance']['revenue']['percentage']);
        $this->assertEquals('increase', $result['variance']['revenue']['direction']);
    }

    /** @test */
    public function it_calculates_growth_rate_correctly()
    {
        // Create trend data with consistent growth
        $baseRevenue = 100000;
        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::create(2024, 1, 1)->addMonths($i);
            $revenue = $baseRevenue * (1.05 ** $i); // 5% monthly growth
            
            $this->createJournalEntry($date, $this->revenueAccount, 0, (int) $revenue);
            $this->createJournalEntry($date, $this->expenseAccount, (int) ($revenue * 0.6), 0);
        }

        $result = $this->comparativeReportService->trendAnalysis(
            $this->company,
            Carbon::create(2024, 12, 31),
            12,
            'month'
        );

        // Growth rate should be approximately 5% per month compounded
        $growthRate = $result['summary']['revenue']['growth_rate'];
        $this->assertGreaterThan(4.0, $growthRate);
        $this->assertLessThan(6.0, $growthRate);
    }

    private function createJournalEntry(Carbon $date, Account $account, int $debit, int $credit): JournalEntry
    {
        $entry = JournalEntry::create([
            'company_id' => $this->company->id,
            'entry_date' => $date->toDateString(),
            'description' => 'Test Entry',
            'status' => JournalEntryStatus::POSTED,
            'posted_at' => $date,
        ]);

        JournalEntryLine::create([
            'company_id' => $this->company->id,
            'journal_entry_id' => $entry->id,
            'account_id' => $account->id,
            'debit' => $debit,
            'credit' => $credit,
            'description' => 'Test Line',
        ]);

        return $entry;
    }
}