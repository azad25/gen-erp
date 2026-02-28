<?php

namespace App\Services\Dashboard;

/**
 * New customers count. Stub until Customer model exists (Phase 3).
 */
class NewCustomersWidget extends BaseWidget
{
    public function getData(): array
    {
        return ['count' => 0];
    }

    public function getViewName(): string
    {
        return 'widgets.new-customers';
    }

    public function getTitle(): string
    {
        return __('New Customers');
    }
}
