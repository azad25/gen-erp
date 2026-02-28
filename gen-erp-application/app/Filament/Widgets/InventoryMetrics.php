<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\StockMovement;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

/**
 * Inventory metrics widget with modern styling.
 */
class InventoryMetrics extends BaseWidget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $totalProducts = Product::count();
        $lowStockProducts = Product::whereColumn('current_stock', '<=', 'min_stock_level')->count();
        $outOfStockProducts = Product::where('current_stock', '<=', 0)->count();
        
        $thisMonth = now()->startOfMonth();
        $stockMovements = StockMovement::where('created_at', '>=', $thisMonth)->count();

        return [
            Stat::make(__('Total Products'), Number::format($totalProducts))
                ->description(__('In inventory'))
                ->descriptionIcon('heroicon-m-cube')
                ->color('info')
                ->extraAttributes(['class' => 'stats-card']),

            Stat::make(__('Low Stock'), Number::format($lowStockProducts))
                ->description(__('Below minimum level'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning')
                ->extraAttributes(['class' => 'stats-card']),

            Stat::make(__('Out of Stock'), Number::format($outOfStockProducts))
                ->description(__('Needs reorder'))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->extraAttributes(['class' => 'stats-card']),

            Stat::make(__('Stock Movements'), Number::format($stockMovements))
                ->description(__('This month'))
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info')
                ->extraAttributes(['class' => 'stats-card']),
        ];
    }
}
