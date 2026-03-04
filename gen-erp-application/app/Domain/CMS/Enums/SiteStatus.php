<?php

namespace App\Domain\CMS\Enums;

enum SiteStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case MAINTENANCE = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::MAINTENANCE => 'Maintenance',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'green',
            self::MAINTENANCE => 'yellow',
        };
    }
}