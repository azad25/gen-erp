<?php

namespace App\Enums;

enum IntegrationCategory: string
{
    case ECOMMERCE = 'ecommerce';
    case COMMUNICATION = 'communication';
    case IOT_HARDWARE = 'iot_hardware';
    case MARKETING = 'marketing';
    case ACCOUNTING = 'accounting';
    case FINANCE = 'finance';
    case GOOGLE = 'google';
    case SOCIAL = 'social';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::ECOMMERCE => __('E-commerce'),
            self::COMMUNICATION => __('Communication'),
            self::IOT_HARDWARE => __('IoT & Hardware'),
            self::MARKETING => __('Marketing'),
            self::ACCOUNTING => __('Accounting'),
            self::FINANCE => __('Finance'),
            self::GOOGLE => __('Google Workspace'),
            self::SOCIAL => __('Social Media'),
            self::CUSTOM => __('Custom'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ECOMMERCE => 'heroicon-o-shopping-cart',
            self::COMMUNICATION => 'heroicon-o-chat-bubble-left-right',
            self::IOT_HARDWARE => 'heroicon-o-cpu-chip',
            self::MARKETING => 'heroicon-o-megaphone',
            self::ACCOUNTING => 'heroicon-o-calculator',
            self::FINANCE => 'heroicon-o-banknotes',
            self::GOOGLE => 'heroicon-o-cloud',
            self::SOCIAL => 'heroicon-o-share',
            self::CUSTOM => 'heroicon-o-cog-6-tooth',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->toArray();
    }
}
