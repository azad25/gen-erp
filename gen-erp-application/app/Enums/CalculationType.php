<?php

namespace App\Enums;

enum CalculationType: string
{
    case FIXED = 'fixed';
    case PERCENTAGE_OF_BASIC = 'percentage_of_basic';
    case PERCENTAGE_OF_GROSS = 'percentage_of_gross';

    public function label(): string
    {
        return match ($this) {
            self::FIXED => __('Fixed Amount'),
            self::PERCENTAGE_OF_BASIC => __('% of Basic'),
            self::PERCENTAGE_OF_GROSS => __('% of Gross'),
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
