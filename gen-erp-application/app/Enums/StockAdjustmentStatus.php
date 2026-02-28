<?php

namespace App\Enums;

enum StockAdjustmentStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case APPLIED = 'applied';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('Draft'),
            self::APPROVED => __('Approved'),
            self::APPLIED => __('Applied'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'warning',
            self::APPROVED => 'info',
            self::APPLIED => 'success',
        };
    }
}
