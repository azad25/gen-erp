<?php

namespace App\Enums;

enum IntegrationTier: string
{
    case NATIVE = 'native';
    case CONNECTOR = 'connector';
    case PLUGIN = 'plugin';

    public function label(): string
    {
        return match ($this) {
            self::NATIVE => __('Native'),
            self::CONNECTOR => __('App Connector'),
            self::PLUGIN => __('Plugin'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NATIVE => 'success',
            self::CONNECTOR => 'info',
            self::PLUGIN => 'warning',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->toArray();
    }
}
