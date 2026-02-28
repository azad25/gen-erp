<?php

namespace App\Services\Dashboard;

use App\Models\Company;

/**
 * Total sales for date range. Stub: returns 0 totals until Invoice model exists (Phase 3).
 */
class TotalSalesWidget extends BaseWidget
{
    public function getData(): array
    {
        // TODO: Phase 3 — query invoices table scoped to company
        return [
            'total_amount' => 0,
            'count' => 0,
            'change_percent' => 0,
            'period' => $this->settings['date_range'] ?? 'month',
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
