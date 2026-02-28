<?php

namespace App\Enums;

/**
 * Types of tax applicable in Bangladesh.
 */
enum TaxType: string
{
    case VAT = 'vat';
    case SD = 'sd';       // Supplementary Duty
    case AIT = 'ait';     // Advance Income Tax

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::VAT => __('VAT'),
            self::SD => __('Supplementary Duty (SD)'),
            self::AIT => __('Advance Income Tax (AIT)'),
        };
    }
}
