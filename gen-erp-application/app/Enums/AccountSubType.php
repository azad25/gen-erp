<?php

namespace App\Enums;

enum AccountSubType: string
{
    case CASH = 'cash';
    case BANK = 'bank';
    case RECEIVABLE = 'receivable';
    case PAYABLE = 'payable';
    case INVENTORY = 'inventory';
    case FIXED_ASSET = 'fixed_asset';
    case CURRENT_LIABILITY = 'current_liability';
    case LONG_TERM_LIABILITY = 'long_term_liability';
    case RETAINED_EARNINGS = 'retained_earnings';
    case REVENUE = 'revenue';
    case COGS = 'cogs';
    case OPERATING_EXPENSE = 'operating_expense';
    case TAX_EXPENSE = 'tax_expense';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CASH => __('Cash'),
            self::BANK => __('Bank'),
            self::RECEIVABLE => __('Receivable'),
            self::PAYABLE => __('Payable'),
            self::INVENTORY => __('Inventory'),
            self::FIXED_ASSET => __('Fixed Asset'),
            self::CURRENT_LIABILITY => __('Current Liability'),
            self::LONG_TERM_LIABILITY => __('Long Term Liability'),
            self::RETAINED_EARNINGS => __('Retained Earnings'),
            self::REVENUE => __('Revenue'),
            self::COGS => __('Cost of Goods Sold'),
            self::OPERATING_EXPENSE => __('Operating Expense'),
            self::TAX_EXPENSE => __('Tax Expense'),
            self::OTHER => __('Other'),
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
