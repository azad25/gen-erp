<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Services\CompanyContext;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class InvoiceStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Invoice Status Distribution';
    protected static ?int $sort = 6;
    protected static ?string $maxHeight = '280px';
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $company = CompanyContext::active();
        
        // Use raw query to avoid enum casting issues with groupBy
        $statuses = DB::table('invoices')
            ->where('company_id', $company->id)
            ->whereNull('deleted_at')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $statusLabels = [
            'draft' => 'Draft',
            'sent' => 'Sent',
            'paid' => 'Paid',
            'partial' => 'Partial',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
        ];

        $statusColors = [
            'draft' => 'rgba(156, 163, 175, 0.8)',
            'sent' => 'rgba(59, 130, 246, 0.8)',
            'paid' => 'rgba(22, 163, 74, 0.8)',
            'partial' => 'rgba(202, 138, 4, 0.8)',
            'overdue' => 'rgba(239, 68, 68, 0.8)',
            'cancelled' => 'rgba(107, 114, 128, 0.8)',
        ];

        $labels = $statuses->map(fn($s) => $statusLabels[$s->status] ?? $s->status)->toArray();
        $data = $statuses->pluck('count')->toArray();
        $colors = $statuses->map(fn($s) => $statusColors[$s->status] ?? 'rgba(156, 163, 175, 0.8)')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Invoices',
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
