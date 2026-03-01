<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Services\CompanyContext;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ProductCategoryChart extends ChartWidget
{
    protected static ?string $heading = 'Products by Category';
    protected static ?int $sort = 5;
    protected static ?string $maxHeight = '280px';
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $company = CompanyContext::active();
        
        $categories = Product::where('company_id', $company->id)
            ->select('category_id', DB::raw('COUNT(*) as count'))
            ->groupBy('category_id')
            ->with('category')
            ->get();

        $labels = $categories->map(fn($p) => $p->category?->name ?? 'Uncategorized')->toArray();
        $data = $categories->pluck('count')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Products',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(15, 118, 110, 0.8)',
                        'rgba(22, 163, 74, 0.8)',
                        'rgba(20, 184, 166, 0.8)',
                        'rgba(202, 138, 4, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
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
