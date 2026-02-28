<?php

namespace App\Services\Dashboard;

/**
 * Total purchases for date range. Stub until PurchaseOrder model exists.
 */
class TotalPurchasesWidget extends BaseWidget
{
    public function getData(): array
    {
        return [
            'total_amount' => 0,
            'count' => 0,
            'change_percent' => 0,
            'period' => $this->settings['date_range'] ?? 'month',
        ];
    }

    public function getViewName(): string
    {
        return 'widgets.total-purchases';
    }

    public function getTitle(): string
    {
        return __('Total Purchases');
    }
}
