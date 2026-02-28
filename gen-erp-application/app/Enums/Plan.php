<?php

namespace App\Enums;

enum Plan: string
{
    case FREE = 'free';
    case PRO = 'pro';
    case ENTERPRISE = 'enterprise';

    /**
     * Human-readable label for this plan.
     */
    public function label(): string
    {
        return match ($this) {
            self::FREE => __('Free'),
            self::PRO => __('Pro'),
            self::ENTERPRISE => __('Enterprise'),
        };
    }

    /**
     * Resource limits for this plan.
     *
     * @return array{users: int, products: int, invoices_per_month: int}
     */
    public function limits(): array
    {
        return match ($this) {
            self::FREE => [
                'users' => 3,
                'products' => 100,
                'invoices_per_month' => 50,
            ],
            self::PRO => [
                'users' => 25,
                'products' => 5000,
                'invoices_per_month' => 1000,
            ],
            self::ENTERPRISE => [
                'users' => PHP_INT_MAX,
                'products' => PHP_INT_MAX,
                'invoices_per_month' => PHP_INT_MAX,
            ],
        };
    }

    /**
     * Whether this is the free plan.
     */
    public function isFree(): bool
    {
        return $this === self::FREE;
    }

    /**
     * Key-value array for Filament Select options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
