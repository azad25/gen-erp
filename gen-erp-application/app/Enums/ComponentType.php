<?php

namespace App\Enums;

enum ComponentType: string
{
    case EARNING = 'earning';
    case DEDUCTION = 'deduction';

    public function label(): string
    {
        return match ($this) {
            self::EARNING => __('Earning'),
            self::DEDUCTION => __('Deduction'),
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
