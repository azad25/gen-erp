<?php

namespace App\Enums;

enum AdjustmentReason: string
{
    case DAMAGE = 'damage';
    case EXPIRY = 'expiry';
    case CORRECTION = 'correction';
    case THEFT = 'theft';
    case FOUND = 'found';
    case AUDIT = 'audit';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DAMAGE => __('Damage'),
            self::EXPIRY => __('Expiry'),
            self::CORRECTION => __('Correction'),
            self::THEFT => __('Theft'),
            self::FOUND => __('Found'),
            self::AUDIT => __('Audit'),
            self::OTHER => __('Other'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DAMAGE, self::THEFT => 'danger',
            self::EXPIRY => 'warning',
            self::FOUND => 'success',
            default => 'gray',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
