<?php

namespace App\Filament\Widgets;

use App\Services\CompanyContext;
use Filament\Widgets\Widget;

/**
 * Top-bar widget allowing users to see and switch their active company.
 */
class CompanySwitcher extends Widget
{
    protected static string $view = 'filament.widgets.company-switcher';

    protected int|string|array $columnSpan = 'full';

    /**
     * Sort order — display at the top.
     */
    protected static ?int $sort = -100;

    /**
     * Get all companies the user belongs to.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Company>
     */
    public function getCompanies(): \Illuminate\Support\Collection
    {
        return auth()->user()->companies()->wherePivot('is_active', true)->get();
    }

    /**
     * Get the currently active company.
     */
    public function getActiveCompany(): ?\App\Models\Company
    {
        return CompanyContext::hasActive() ? CompanyContext::active() : null;
    }
}
