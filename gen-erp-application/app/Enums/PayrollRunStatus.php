<?php

namespace App\Enums;

enum PayrollRunStatus: string
{
    case DRAFT = 'draft';
    case PROCESSING = 'processing';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('Draft'),
            self::PROCESSING => __('Processing'),
            self::APPROVED => __('Approved'),
            self::PAID => __('Paid'),
            self::CANCELLED => __('Cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PROCESSING => 'warning',
            self::APPROVED => 'success',
            self::PAID => 'info',
            self::CANCELLED => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
