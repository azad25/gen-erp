<?php

namespace App\Support\Enums;

/**
 * Journal code categorisation for journal entries.
 */
enum JournalCode: string
{
    case GENERAL = 'general';
    case SALES = 'sales';
    case PURCHASE = 'purchase';
    case BANK = 'bank';
    case CASH = 'cash';
    case PAYROLL = 'payroll';
    case INVENTORY = 'inventory';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL => __('General'),
            self::SALES => __('Sales'),
            self::PURCHASE => __('Purchase'),
            self::BANK => __('Bank'),
            self::CASH => __('Cash'),
            self::PAYROLL => __('Payroll'),
            self::INVENTORY => __('Inventory'),
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
