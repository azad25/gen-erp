<?php

namespace App\Domain\CRM\Enums;

enum LeadStatus: string
{
    case NEW = 'new';
    case CONTACTED = 'contacted';
    case QUALIFIED = 'qualified';
    case UNQUALIFIED = 'unqualified';
    case CONVERTED = 'converted';

    public function label(): string
    {
        return match($this) {
            self::NEW => 'New',
            self::CONTACTED => 'Contacted',
            self::QUALIFIED => 'Qualified',
            self::UNQUALIFIED => 'Unqualified',
            self::CONVERTED => 'Converted',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NEW => '#6B7280',
            self::CONTACTED => '#3B82F6',
            self::QUALIFIED => '#10B981',
            self::UNQUALIFIED => '#EF4444',
            self::CONVERTED => '#8B5CF6',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::NEW => 'Newly created lead, not yet contacted',
            self::CONTACTED => 'Lead has been contacted but not yet qualified',
            self::QUALIFIED => 'Lead meets qualification criteria',
            self::UNQUALIFIED => 'Lead does not meet qualification criteria',
            self::CONVERTED => 'Lead has been converted to customer',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())->map(fn($status) => [
            'value' => $status->value,
            'label' => $status->label(),
            'color' => $status->color(),
            'description' => $status->description(),
        ])->toArray();
    }
}