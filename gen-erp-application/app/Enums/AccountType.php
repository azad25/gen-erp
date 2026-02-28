<?php

namespace App\Enums;

enum AccountType: string
{
    case ASSET = 'asset';
    case LIABILITY = 'liability';
    case EQUITY = 'equity';
    case INCOME = 'income';
    case EXPENSE = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::ASSET => __('Asset'),
            self::LIABILITY => __('Liability'),
            self::EQUITY => __('Equity'),
            self::INCOME => __('Income'),
            self::EXPENSE => __('Expense'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ASSET => 'info',
            self::LIABILITY => 'danger',
            self::EQUITY => 'success',
            self::INCOME => 'success',
            self::EXPENSE => 'warning',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
