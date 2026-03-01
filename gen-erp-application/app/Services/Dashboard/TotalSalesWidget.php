<?php

namespace App\Services\Dashboard;

use App\Models\Company;
use App\Services\CompanyContext;

/**
 * Total sales for date range. Stub: returns 0 totals until Invoice model exists (Phase 3).
 */
class TotalSalesWidget extends BaseWidget
{
    public function getData(): array
    {
        $companyId = CompanyContext::activeId();
        $dateRange = $this->settings['date_range'] ?? 'month';
        
        $startDate = match($dateRange) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };
        
        $invoices = \App\Models\Invoice::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'paid')
            ->where('invoice_date', '>=', $startDate)
            ->get();
        
        $totalAmount = $invoices->sum('total_amount');
        $count = $invoices->count();
        
        // Calculate change percent compared to previous period
        $previousPeriodStart = $startDate->copy()->subDays((int) $startDate->diffInDays(now()));
        $previousPeriodEnd = $startDate->copy()->subDay();
        
        $previousTotal = \App\Models\Invoice::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'paid')
            ->whereBetween('invoice_date', [$previousPeriodStart, $previousPeriodEnd])
            ->sum('total_amount') ?? 0;
        
        $changePercent = $previousTotal > 0 
            ? (($totalAmount - $previousTotal) / $previousTotal) * 100 
            : 0;
        
        return [
            'total_amount' => $totalAmount,
            'count' => $count,
            'change_percent' => round($changePercent, 1),
            'period' => $dateRange,
        ];
    }

    public function getViewName(): string
    {
        return 'widgets.total-sales';
    }

    public function getTitle(): string
    {
        return __('Total Sales');
    }
}
