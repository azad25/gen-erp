<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Services\CompanyContext;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SalesVsPurchasesChart extends ChartWidget
{
    protected static ?string $heading = 'Sales vs Purchases';
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '280px';
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $company = CompanyContext::active();
        
        // Get last 6 months
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(now()->subMonths($i)->format('Y-m'));
        }

        // Get sales data
        $sales = Invoice::where('company_id', $company->id)
            ->where('created_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        // Get purchase data
        $purchases = PurchaseOrder::where('company_id', $company->id)
            ->where('created_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        $salesData = $months->map(fn($m) => $sales->get($m)?->total ?? 0)->toArray();
        $purchasesData = $months->map(fn($m) => $purchases->get($m)?->total ?? 0)->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => $salesData,
                    'backgroundColor' => 'rgba(15, 118, 110, 0.8)',
                    'borderColor' => 'rgba(15, 118, 110, 1)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Purchases',
                    'data' => $purchasesData,
                    'backgroundColor' => 'rgba(202, 138, 4, 0.8)',
                    'borderColor' => 'rgba(202, 138, 4, 1)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $months->map(fn($m) => date('M Y', strtotime($m.'-01')))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
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
