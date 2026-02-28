<?php

namespace App\Services;

use App\Enums\WidgetType;
use App\Models\Company;
use App\Models\DashboardWidget;
use App\Models\User;
use App\Services\Dashboard\BaseWidget;
use App\Services\Dashboard\CashBalanceWidget;
use App\Services\Dashboard\LowStockWidget;
use App\Services\Dashboard\NewCustomersWidget;
use App\Services\Dashboard\PendingApprovalsWidget;
use App\Services\Dashboard\TotalPurchasesWidget;
use App\Services\Dashboard\TotalSalesWidget;
use Illuminate\Database\Eloquent\Collection;

/**
 * Manages dashboard widget layout and data resolution.
 */
class DashboardService
{
    /**
     * Get widgets for a user, ordered by grid position.
     *
     * @return Collection<int, DashboardWidget>
     */
    public function getWidgetsForUser(User $user, Company $company): Collection
    {
        return DashboardWidget::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where(function ($q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->where('is_active', true)
            ->orderBy('position_y')
            ->orderBy('position_x')
            ->get();
    }

    /**
     * Save widget layout positions from drag-drop.
     *
     * @param  array<int, array{id: int, x: int, y: int, width: int, height: int}>  $layout
     */
    public function saveLayout(User $user, Company $company, array $layout): void
    {
        foreach ($layout as $item) {
            DashboardWidget::withoutGlobalScopes()
                ->where('id', $item['id'])
                ->where('company_id', $company->id)
                ->update([
                    'position_x' => $item['x'],
                    'position_y' => $item['y'],
                    'width' => $item['width'],
                    'height' => $item['height'],
                ]);
        }
    }

    /**
     * Create a new widget for a user.
     *
     * @param  array<string, mixed>  $settings
     */
    public function createWidget(User $user, Company $company, WidgetType $type, array $settings = []): DashboardWidget
    {
        return DashboardWidget::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'widget_type' => $type->value,
            'width' => $type->defaultWidth(),
            'height' => $type->defaultHeight(),
            'settings' => $settings,
        ]);
    }

    /**
     * Resolve a DashboardWidget DB record to its concrete BaseWidget implementation.
     */
    public function resolveWidget(DashboardWidget $widget): BaseWidget
    {
        $company = $widget->company ?? Company::find($widget->company_id);

        return match ($widget->widget_type) {
            WidgetType::TOTAL_SALES => new TotalSalesWidget($company, $widget->settings ?? []),
            WidgetType::TOTAL_PURCHASES => new TotalPurchasesWidget($company, $widget->settings ?? []),
            WidgetType::CASH_BALANCE => new CashBalanceWidget($company, $widget->settings ?? []),
            WidgetType::LOW_STOCK => new LowStockWidget($company, $widget->settings ?? []),
            WidgetType::PENDING_APPROVALS => new PendingApprovalsWidget($company, $widget->settings ?? []),
            WidgetType::NEW_CUSTOMERS => new NewCustomersWidget($company, $widget->settings ?? []),
            default => new TotalSalesWidget($company, $widget->settings ?? []),
        };
    }
}
