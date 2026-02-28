<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\SalesOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

/**
 * Sales metrics widget with modern styling.
 */
class SalesMetrics extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        $thisMonthSales = Invoice::where('status', 'paid')
            ->where('created_at', '>=', $thisMonth)
            ->sum('total_amount');

        $lastMonthSales = Invoice::where('status', 'paid')
            ->whereBetween('created_at', [$lastMonth, $thisMonth])
            ->sum('total_amount');

        $growth = $lastMonthSales > 0 
            ? (($thisMonthSales - $lastMonthSales) / $lastMonthSales) * 100 
            : 0;

        $pendingOrders = SalesOrder::whereIn('status', ['draft', 'confirmed'])->count();
        $completedOrders = SalesOrder::where('status', 'fulfilled')
            ->where('created_at', '>=', $thisMonth)
            ->count();

        return [
            Stat::make(__('This Month Sales'), '৳ '.Number::format($thisMonthSales / 100, 2))
                ->description($growth >= 0 ? "+{$growth}% from last month" : "{$growth}% from last month")
                ->descriptionIcon($growth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart([12, 15, 18, 22, 25, 28, 30])
                ->color($growth >= 0 ? 'success' : 'danger')
                ->extraAttributes(['class' => 'stats-card']),

            Stat::make(__('Pending Orders'), Number::format($pendingOrders))
                ->description(__('Awaiting processing'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->extraAttributes(['class' => 'stats-card']),

            Stat::make(__('Completed Orders'), Number::format($completedOrders))
                ->description(__('This month'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->extraAttributes(['class' => 'stats-card']),
        ];
    }
}
