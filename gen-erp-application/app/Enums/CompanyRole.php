<?php

namespace App\Enums;

enum CompanyRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case ACCOUNTANT = 'accountant';
    case HR_MANAGER = 'hr_manager';
    case SALES = 'sales';
    case PURCHASE = 'purchase';
    case WAREHOUSE = 'warehouse';
    case EMPLOYEE = 'employee';
    case VIEWER = 'viewer';

    /**
     * Human-readable label for this role.
     */
    public function label(): string
    {
        return match ($this) {
            self::OWNER => __('Owner'),
            self::ADMIN => __('Administrator'),
            self::MANAGER => __('Manager'),
            self::ACCOUNTANT => __('Accountant'),
            self::HR_MANAGER => __('HR Manager'),
            self::SALES => __('Sales'),
            self::PURCHASE => __('Purchase'),
            self::WAREHOUSE => __('Warehouse'),
            self::EMPLOYEE => __('Employee'),
            self::VIEWER => __('Viewer'),
        };
    }

    /**
     * Default permissions assigned to this role.
     *
     * @return array<int, string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::OWNER => [
                'company.manage',
                'users.manage',
                'roles.manage',
                'products.manage',
                'customers.manage',
                'suppliers.manage',
                'orders.manage',
                'invoices.manage',
                'payments.manage',
                'inventory.manage',
                'reports.view',
                'settings.manage',
                'hr.manage',
                'accounting.manage',
                'audit_logs.view',
            ],
            self::ADMIN => [
                'users.manage',
                'roles.manage',
                'products.manage',
                'customers.manage',
                'suppliers.manage',
                'orders.manage',
                'invoices.manage',
                'payments.manage',
                'inventory.manage',
                'reports.view',
                'settings.manage',
                'hr.manage',
                'accounting.manage',
                'audit_logs.view',
            ],
            self::MANAGER => [
                'products.manage',
                'customers.manage',
                'suppliers.manage',
                'orders.manage',
                'invoices.manage',
                'payments.manage',
                'inventory.manage',
                'reports.view',
            ],
            self::ACCOUNTANT => [
                'invoices.manage',
                'payments.manage',
                'accounting.manage',
                'reports.view',
                'customers.view',
                'suppliers.view',
            ],
            self::HR_MANAGER => [
                'hr.manage',
                'users.view',
                'reports.view',
            ],
            self::SALES => [
                'customers.manage',
                'orders.manage',
                'invoices.view',
                'products.view',
                'inventory.view',
            ],
            self::PURCHASE => [
                'suppliers.manage',
                'products.view',
                'inventory.view',
                'orders.manage',
            ],
            self::WAREHOUSE => [
                'inventory.manage',
                'products.view',
                'orders.view',
            ],
            self::EMPLOYEE => [
                'products.view',
                'orders.view',
            ],
            self::VIEWER => [
                'products.view',
                'customers.view',
                'orders.view',
                'invoices.view',
                'reports.view',
            ],
        };
    }

    /**
     * Key-value array for Filament Select options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
