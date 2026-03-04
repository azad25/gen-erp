<?php

namespace App\Domain\Logistics\Enums;

enum DeliveryType: string
{
    case STANDARD = 'standard';
    case EXPRESS = 'express';
    case SAME_DAY = 'same_day';
    case NEXT_DAY = 'next_day';

    public function label(): string
    {
        return match($this) {
            self::STANDARD => 'Standard Delivery',
            self::EXPRESS => 'Express Delivery',
            self::SAME_DAY => 'Same Day Delivery',
            self::NEXT_DAY => 'Next Day Delivery',
        };
    }

    public function expectedDays(): int
    {
        return match($this) {
            self::STANDARD => 3,
            self::EXPRESS => 2,
            self::SAME_DAY => 0,
            self::NEXT_DAY => 1,
        };
    }

    public function multiplier(): float
    {
        return match($this) {
            self::STANDARD => 1.0,
            self::EXPRESS => 1.5,
            self::SAME_DAY => 2.5,
            self::NEXT_DAY => 2.0,
        };
    }
}