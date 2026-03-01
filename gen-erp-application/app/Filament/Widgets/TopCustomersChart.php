<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Services\CompanyContext;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopCustomersChart extends ChartWidget
{
    protected static ?string $heading = 'Top 5 Customers';
    protected static ?int $sort = 4;
    protected static ?string $maxHeight = '280px';
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $company = CompanyContext::active();
        
        $topCustomers = Invoice::where('company_id', $company->id)
            ->where('status', '!=', 'cancelled')
            ->select('customer_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('customer_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('customer')
            ->get();

        $labels = $topCustomers->map(fn($inv) => $inv->customer?->name ?? 'Unknown')->toArray();
        $data = $topCustomers->map(fn($inv) => $inv->total)->toArray();

        $colors = [
            'rgba(15, 118, 110, 0.8)',
            'rgba(22, 163, 74, 0.8)',
            'rgba(20, 184, 166, 0.8)',
            'rgba(202, 138, 4, 0.8)',
            'rgba(239, 68, 68, 0.8)',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
