<?php

namespace App\Enums;

/**
 * Product type classification.
 */
enum ProductType: string
{
    case PRODUCT = 'product';
    case SERVICE = 'service';
    case DIGITAL = 'digital';

    public function label(): string
    {
        return match ($this) {
            self::PRODUCT => __('Physical Product'),
            self::SERVICE => __('Service'),
            self::DIGITAL => __('Digital Product'),
        };
    }

    /**
     * Whether this type should track inventory stock.
     */
    public function tracksInventory(): bool
    {
        return match ($this) {
            self::PRODUCT => true,
            self::SERVICE, self::DIGITAL => false,
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
