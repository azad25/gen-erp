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
 * Modern stats overview widget with Geex-inspired minimalist cards.
 */
class ModernStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $totalRevenue = Invoice::where('status', 'paid')
            ->sum('total_amount');

        $pendingOrders = SalesOrder::whereIn('status', ['draft', 'confirmed'])
            ->count();

        $totalCustomers = Customer::count();

        $cardClasses = 'border-none rounded-3xl bg-white shadow-[0_8px_30px_rgb(0,0,0,0.04)] ring-0';

        return [
            Stat::make(__('Total Revenue'), '৳ '.Number::format($totalRevenue / 100, 2))
                ->description(__('Total sales this month'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3])
                ->color('success')
                ->extraAttributes([
                    'class' => $cardClasses,
                ]),

            Stat::make(__('Pending Orders'), Number::format($pendingOrders))
                ->description(__('Awaiting fulfillment'))
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->chart([3, 2, 4, 3, 4, 6, 5])
                ->color('warning')
                ->extraAttributes([
                    'class' => $cardClasses,
                ]),

            Stat::make(__('Total Customers'), Number::format($totalCustomers))
                ->description(__('Active customer base'))
                ->descriptionIcon('heroicon-m-user-group')
                ->chart([3, 5, 7, 9, 11, 13, 15])
                ->color('info')
                ->extraAttributes([
                    'class' => $cardClasses,
                ]),
        ];
    }
}
