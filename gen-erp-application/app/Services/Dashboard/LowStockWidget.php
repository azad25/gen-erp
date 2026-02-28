<?php

namespace App\Services\Dashboard;

/**
 * Low stock items. Stub until Inventory module exists (Phase 3).
 */
class LowStockWidget extends BaseWidget
{
    public function getData(): array
    {
        return ['items' => []];
    }

    public function getViewName(): string
    {
        return 'widgets.low-stock';
    }

    public function getTitle(): string
    {
        return __('Low Stock');
    }
}
