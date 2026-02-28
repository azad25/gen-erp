<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

/**
 * Modern stats overview widget with gradient cards and icons.
 */
class ModernStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalRevenue = Invoice::where('status', 'paid')
            ->sum('total_amount');

        $pendingOrders = SalesOrder::whereIn('status', ['draft', 'confirmed'])
            ->count();

        $totalCustomers = Customer::count();

        $lowStockProducts = Product::whereColumn('current_stock', '<=', 'min_stock_level')
            ->count();

        return [
            Stat::make(__('Total Revenue'), '৳ '.Number::format($totalRevenue / 100, 2))
                ->description(__('Total sales this month'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3])
                ->color('success')
                ->extraAttributes([
                    'class' => 'stats-card',
                ]),

            Stat::make(__('Pending Orders'), Number::format($pendingOrders))
                ->description(__('Awaiting fulfillment'))
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'stats-card',
                ]),

            Stat::make(__('Total Customers'), Number::format($totalCustomers))
                ->description(__('Active customer base'))
                ->descriptionIcon('heroicon-m-user-group')
                ->chart([3, 5, 7, 9, 11, 13, 15])
                ->color('info')
                ->extraAttributes([
                    'class' => 'stats-card',
                ]),

            Stat::make(__('Low Stock Items'), Number::format($lowStockProducts))
                ->description(__('Products below minimum'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->extraAttributes([
                    'class' => 'stats-card',
                ]),
        ];
    }
}
