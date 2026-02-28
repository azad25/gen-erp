<?php

namespace App\Enums;

enum BusinessType: string
{
    case RETAIL = 'retail';
    case PHARMACY = 'pharmacy';
    case WHOLESALE = 'wholesale';
    case MANUFACTURING = 'manufacturing';
    case RMG = 'rmg';
    case RESTAURANT = 'restaurant';
    case SERVICE = 'service';
    case FREELANCER = 'freelancer';
    case NGO = 'ngo';
    case ECOMMERCE = 'ecommerce';
    case SCHOOL = 'school';
    case GOVERNMENT = 'government';
    case OTHER = 'other';

    /**
     * Human-readable label for this business type.
     */
    public function label(): string
    {
        return match ($this) {
            self::RETAIL => __('Retail Store'),
            self::PHARMACY => __('Pharmacy / Dispensary'),
            self::WHOLESALE => __('Wholesale / Distributor'),
            self::MANUFACTURING => __('Manufacturing'),
            self::RMG => __('Garments / RMG Factory'),
            self::RESTAURANT => __('Restaurant / Food Service'),
            self::SERVICE => __('Service Provider'),
            self::FREELANCER => __('Freelancer / Solo'),
            self::NGO => __('NGO / Non-Profit'),
            self::ECOMMERCE => __('E-Commerce'),
            self::SCHOOL => __('School / Educational Institute'),
            self::GOVERNMENT => __('Government'),
            self::OTHER => __('Other'),
        };
    }

    /**
     * Heroicon name for this business type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::RETAIL => 'heroicon-o-shopping-bag',
            self::PHARMACY => 'heroicon-o-beaker',
            self::WHOLESALE => 'heroicon-o-truck',
            self::MANUFACTURING => 'heroicon-o-cog-6-tooth',
            self::RMG => 'heroicon-o-scissors',
            self::RESTAURANT => 'heroicon-o-cake',
            self::SERVICE => 'heroicon-o-wrench-screwdriver',
            self::FREELANCER => 'heroicon-o-user',
            self::NGO => 'heroicon-o-heart',
            self::ECOMMERCE => 'heroicon-o-globe-alt',
            self::SCHOOL => 'heroicon-o-academic-cap',
            self::GOVERNMENT => 'heroicon-o-building-library',
            self::OTHER => 'heroicon-o-squares-2x2',
        };
    }

    /**
     * Whether this business type defaults to simplified (less complex) mode.
     */
    public function simplifiedModeDefault(): bool
    {
        return match ($this) {
            self::FREELANCER, self::SERVICE => true,
            default => false,
        };
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
