<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\CompanyContext;
use App\Models\Invoice;
use App\Models\StockLevel;
use App\Models\WorkflowApproval;

class ModernStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $company = CompanyContext::active();

        // Safe fallback if company isn't loaded (e.g. during CLI or early boot)
        $companyId = $company ? $company->id : null;

        $revenueThisMonth = 0;
        $outstanding = 0;
        $lowStock = 0;
        $pendingApprovals = 0;

        if ($companyId) {
            $revenueThisMonth = Invoice::where('company_id', $companyId)
                ->whereMonth('invoice_date', now()->month)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount') ?? 0;

            $outstanding = Invoice::where('company_id', $companyId)
                ->whereIn('status', ['sent', 'partial', 'overdue'])
                ->sum('balance_due') ?? 0;

            if (class_exists(StockLevel::class)) {
                $lowStock = StockLevel::where('company_id', $companyId)
                    ->whereRaw('(quantity - reserved_quantity) <= (
                        SELECT low_stock_threshold FROM products
                        WHERE products.id = stock_levels.product_id
                    )')
                    ->count();
            }

            if (class_exists(WorkflowApproval::class)) {
                $pendingApprovals = WorkflowApproval::where('company_id', $companyId)
                    ->where('status', 'pending')
                    ->count();
            }
        }

        return [
            Stat::make('Revenue This Month', '৳' . number_format($revenueThisMonth / 100, 2))
                ->description('vs last month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([40, 55, 35, 70, 60, 80, 95])
                ->extraAttributes(['class' => 'fi-wi-stats-overview-stat']),

            Stat::make('Outstanding', '৳' . number_format($outstanding / 100, 2))
                ->description('receivables')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info')
                ->extraAttributes(['class' => 'fi-wi-stats-overview-stat']),

            Stat::make('Low Stock Alerts', $lowStock)
                ->description('products below threshold')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStock > 0 ? 'warning' : 'success')
                ->extraAttributes(['class' => 'fi-wi-stats-overview-stat']),

            Stat::make('Pending Approvals', $pendingApprovals)
                ->description('awaiting action')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingApprovals > 0 ? 'danger' : 'success')
                ->extraAttributes(['class' => 'fi-wi-stats-overview-stat']),
        ];
    }
}
