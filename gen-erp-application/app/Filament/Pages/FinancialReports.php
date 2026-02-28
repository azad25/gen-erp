<?php

namespace App\Filament\Pages;

use App\Services\AccountingService;
use App\Services\CompanyContext;
use Carbon\Carbon;
use Filament\Pages\Page;

class FinancialReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Financial Reports';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.financial-reports';

    public string $activeTab = 'trial_balance';

    public ?string $asOfDate = null;

    public ?string $fromDate = null;

    public ?string $toDate = null;

    /** @var array<string, mixed> */
    public array $reportData = [];

    public function mount(): void
    {
        $this->asOfDate = now()->toDateString();
        $this->fromDate = now()->startOfYear()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function generateReport(): void
    {
        $service = app(AccountingService::class);
        $company = CompanyContext::getActive();

        if (! $company) {
            return;
        }

        $this->reportData = match ($this->activeTab) {
            'trial_balance' => $service->getTrialBalance($company, Carbon::parse($this->asOfDate)),
            'profit_loss' => $service->getProfitAndLoss($company, Carbon::parse($this->fromDate), Carbon::parse($this->toDate)),
            'balance_sheet' => $service->getBalanceSheet($company, Carbon::parse($this->asOfDate)),
            default => [],
        };
    }

    public static function getNavigationLabel(): string
    {
        return __('Financial Reports');
    }
}
