<?php

namespace App\Domain\Logistics\Enums;

enum CarrierType: string
{
    case PATHAO = 'pathao';
    case PAPERFLY = 'paperfly';
    case STEADFAST = 'steadfast';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match($this) {
            self::PATHAO => 'Pathao',
            self::PAPERFLY => 'PaperFly',
            self::STEADFAST => 'SteadFast',
            self::CUSTOM => 'Custom Carrier',
        };
    }

    public function apiClass(): string
    {
        return match($this) {
            self::PATHAO => \App\Domain\Logistics\Integrations\PathaoCarrier::class,
            self::PAPERFLY => \App\Domain\Logistics\Integrations\PaperFlyCarrier::class,
            self::STEADFAST => \App\Domain\Logistics\Integrations\SteadFastCarrier::class,
            self::CUSTOM => \App\Domain\Logistics\Integrations\CustomCarrier::class,
        };
    }

    public function supportsCOD(): bool
    {
        return match($this) {
            self::PATHAO => true,
            self::PAPERFLY => true,
            self::STEADFAST => true,
            self::CUSTOM => false,
        };
    }

    public function supportsTracking(): bool
    {
        return match($this) {
            self::PATHAO => true,
            self::PAPERFLY => true,
            self::STEADFAST => true,
            self::CUSTOM => false,
        };
    }
}