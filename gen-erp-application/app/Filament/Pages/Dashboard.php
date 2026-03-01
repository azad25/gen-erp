<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Modern dashboard with custom GenERP BD layout.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.pages.dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\ModernStatsOverview::class,
            \App\Filament\Widgets\MonthlyRevenueChart::class,
            \App\Filament\Widgets\SalesVsPurchasesChart::class,
            \App\Filament\Widgets\TopCustomersChart::class,
            \App\Filament\Widgets\InvoiceStatusChart::class,
            \App\Filament\Widgets\ProductCategoryChart::class,
            \App\Filament\Widgets\RecentInvoicesWidget::class,
            \App\Filament\Widgets\ActivityFeedWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
            'xl' => 2,
            '2xl' => 2,
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [];
    }

    public function getFooterWidgets(): array
    {
        return [];
    }
}
