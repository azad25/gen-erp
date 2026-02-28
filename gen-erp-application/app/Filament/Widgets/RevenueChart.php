<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/**
 * Modern revenue chart with gradient styling.
 */
class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue Overview';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Get revenue data for the last 6 months
        $data = Invoice::where('status', 'paid')
            ->where('created_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Generate all months in the range
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(now()->subMonths($i)->format('Y-m'));
        }

        // Map data to months (fill missing months with 0)
        $revenueByMonth = $months->mapWithKeys(function ($month) use ($data) {
            $record = $data->firstWhere('month', $month);
            return [$month => $record ? $record->total / 100 : 0];
        });

        return [
            'datasets' => [
                [
                    'label' => __('Revenue'),
                    'data' => $revenueByMonth->values()->toArray(),
                    'backgroundColor' => 'rgba(147, 51, 234, 0.1)',
                    'borderColor' => 'rgba(147, 51, 234, 1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months->map(fn ($month) => date('M Y', strtotime($month.'-01')))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
}
