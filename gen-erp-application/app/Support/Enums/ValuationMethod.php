<?php

namespace App\Support\Enums;

/**
 * Inventory valuation/costing method.
 */
enum ValuationMethod: string
{
    case FIFO = 'fifo';
    case WEIGHTED_AVERAGE = 'weighted_average';

    public function label(): string
    {
        return match ($this) {
            self::FIFO => __('FIFO (First In, First Out)'),
            self::WEIGHTED_AVERAGE => __('Weighted Average Cost'),
        };
    }
}
