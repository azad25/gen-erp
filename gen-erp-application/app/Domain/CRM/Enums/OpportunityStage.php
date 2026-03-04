<?php

namespace App\Domain\CRM\Enums;

enum OpportunityStage: string
{
    case PROSPECTING = 'prospecting';
    case QUALIFICATION = 'qualification';
    case NEEDS_ANALYSIS = 'needs_analysis';
    case PROPOSAL = 'proposal';
    case NEGOTIATION = 'negotiation';
    case CLOSED_WON = 'closed_won';
    case CLOSED_LOST = 'closed_lost';

    public function label(): string
    {
        return match($this) {
            self::PROSPECTING => 'Prospecting',
            self::QUALIFICATION => 'Qualification',
            self::NEEDS_ANALYSIS => 'Needs Analysis',
            self::PROPOSAL => 'Proposal',
            self::NEGOTIATION => 'Negotiation',
            self::CLOSED_WON => 'Closed Won',
            self::CLOSED_LOST => 'Closed Lost',
        };
    }

    public function probability(): int
    {
        return match($this) {
            self::PROSPECTING => 10,
            self::QUALIFICATION => 25,
            self::NEEDS_ANALYSIS => 50,
            self::PROPOSAL => 75,
            self::NEGOTIATION => 90,
            self::CLOSED_WON => 100,
            self::CLOSED_LOST => 0,
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PROSPECTING => '#6B7280',
            self::QUALIFICATION => '#3B82F6',
            self::NEEDS_ANALYSIS => '#F59E0B',
            self::PROPOSAL => '#8B5CF6',
            self::NEGOTIATION => '#EF4444',
            self::CLOSED_WON => '#10B981',
            self::CLOSED_LOST => '#6B7280',
        };
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::CLOSED_WON, self::CLOSED_LOST]);
    }

    public function isWon(): bool
    {
        return $this === self::CLOSED_WON;
    }

    public function isLost(): bool
    {
        return $this === self::CLOSED_LOST;
    }

    public static function getOptions(): array
    {
        return collect(self::cases())->map(fn($stage) => [
            'value' => $stage->value,
            'label' => $stage->label(),
            'probability' => $stage->probability(),
            'color' => $stage->color(),
            'is_closed' => $stage->isClosed(),
            'is_won' => $stage->isWon(),
            'is_lost' => $stage->isLost(),
        ])->toArray();
    }
}