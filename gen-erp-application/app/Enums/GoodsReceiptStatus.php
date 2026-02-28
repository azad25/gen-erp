<?php

namespace App\Enums;

enum GoodsReceiptStatus: string
{
    case DRAFT = 'draft';
    case VERIFIED = 'verified';
    case POSTED = 'posted';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('Draft'),
            self::VERIFIED => __('Verified'),
            self::POSTED => __('Posted'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::VERIFIED => 'info',
            self::POSTED => 'success',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s): array => [$s->value => $s->label()])
            ->all();
    }
}
