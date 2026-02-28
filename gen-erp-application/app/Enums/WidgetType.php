<?php

namespace App\Enums;

/**
 * Available dashboard widget types.
 */
enum WidgetType: string
{
    case TOTAL_SALES = 'total_sales';
    case TOTAL_PURCHASES = 'total_purchases';
    case CASH_BALANCE = 'cash_balance';
    case LOW_STOCK = 'low_stock';
    case EXPIRY_ALERTS = 'expiry_alerts';
    case PENDING_APPROVALS = 'pending_approvals';
    case ATTENDANCE_SUMMARY = 'attendance_summary';
    case OUTSTANDING_RECEIVABLES = 'outstanding_receivables';
    case OUTSTANDING_PAYABLES = 'outstanding_payables';
    case BEST_SELLING_PRODUCTS = 'best_selling_products';
    case PENDING_SALARY_RUN = 'pending_salary_run';
    case NEW_CUSTOMERS = 'new_customers';
    case EXPENSE_SUMMARY = 'expense_summary';
    case VAT_DUE = 'vat_due';

    public function label(): string
    {
        return match ($this) {
            self::TOTAL_SALES => __('Total Sales'),
            self::TOTAL_PURCHASES => __('Total Purchases'),
            self::CASH_BALANCE => __('Cash Balance'),
            self::LOW_STOCK => __('Low Stock'),
            self::EXPIRY_ALERTS => __('Expiry Alerts'),
            self::PENDING_APPROVALS => __('Pending Approvals'),
            self::ATTENDANCE_SUMMARY => __('Attendance Summary'),
            self::OUTSTANDING_RECEIVABLES => __('Outstanding Receivables'),
            self::OUTSTANDING_PAYABLES => __('Outstanding Payables'),
            self::BEST_SELLING_PRODUCTS => __('Best Selling Products'),
            self::PENDING_SALARY_RUN => __('Pending Salary Run'),
            self::NEW_CUSTOMERS => __('New Customers'),
            self::EXPENSE_SUMMARY => __('Expense Summary'),
            self::VAT_DUE => __('VAT Due'),
        };
    }

    public function defaultWidth(): int
    {
        return match ($this) {
            self::TOTAL_SALES, self::TOTAL_PURCHASES, self::CASH_BALANCE,
            self::PENDING_APPROVALS, self::NEW_CUSTOMERS => 3,
            self::LOW_STOCK, self::BEST_SELLING_PRODUCTS => 4,
            default => 3,
        };
    }

    public function defaultHeight(): int
    {
        return match ($this) {
            self::LOW_STOCK, self::BEST_SELLING_PRODUCTS => 2,
            default => 1,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
