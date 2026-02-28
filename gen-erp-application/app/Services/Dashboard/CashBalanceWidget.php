<?php

namespace App\Services\Dashboard;

/**
 * Cash balance. Stub until Accounts module exists (Phase 5).
 */
class CashBalanceWidget extends BaseWidget
{
    public function getData(): array
    {
        return ['balance' => 0];
    }

    public function getViewName(): string
    {
        return 'widgets.cash-balance';
    }

    public function getTitle(): string
    {
        return __('Cash Balance');
    }
}
