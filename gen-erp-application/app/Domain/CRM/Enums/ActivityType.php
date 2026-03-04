<?php

namespace App\Domain\CRM\Enums;

enum ActivityType: string
{
    case CALL = 'call';
    case EMAIL = 'email';
    case MEETING = 'meeting';
    case TASK = 'task';
    case NOTE = 'note';
    case SMS = 'sms';
    case FOLLOW_UP = 'follow_up';
    case DEMO = 'demo';
    case PROPOSAL_SENT = 'proposal_sent';
    case CONTRACT_SENT = 'contract_sent';
    case PAYMENT_RECEIVED = 'payment_received';
    case COMPLAINT = 'complaint';
    case SUPPORT = 'support';

    public function label(): string
    {
        return match($this) {
            self::CALL => 'Phone Call',
            self::EMAIL => 'Email',
            self::MEETING => 'Meeting',
            self::TASK => 'Task',
            self::NOTE => 'Note',
            self::SMS => 'SMS',
            self::FOLLOW_UP => 'Follow Up',
            self::DEMO => 'Demo',
            self::PROPOSAL_SENT => 'Proposal Sent',
            self::CONTRACT_SENT => 'Contract Sent',
            self::PAYMENT_RECEIVED => 'Payment Received',
            self::COMPLAINT => 'Complaint',
            self::SUPPORT => 'Support',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::CALL => 'phone',
            self::EMAIL => 'mail',
            self::MEETING => 'calendar',
            self::TASK => 'clipboard-check',
            self::NOTE => 'document-text',
            self::SMS => 'chat',
            self::FOLLOW_UP => 'arrow-right',
            self::DEMO => 'presentation-chart-line',
            self::PROPOSAL_SENT => 'document-duplicate',
            self::CONTRACT_SENT => 'document-text',
            self::PAYMENT_RECEIVED => 'currency-dollar',
            self::COMPLAINT => 'exclamation-triangle',
            self::SUPPORT => 'support',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::CALL => '#3B82F6',
            self::EMAIL => '#10B981',
            self::MEETING => '#8B5CF6',
            self::TASK => '#F59E0B',
            self::NOTE => '#6B7280',
            self::SMS => '#06B6D4',
            self::FOLLOW_UP => '#EF4444',
            self::DEMO => '#8B5CF6',
            self::PROPOSAL_SENT => '#F59E0B',
            self::CONTRACT_SENT => '#10B981',
            self::PAYMENT_RECEIVED => '#059669',
            self::COMPLAINT => '#DC2626',
            self::SUPPORT => '#3B82F6',
        };
    }

    public function requiresOutcome(): bool
    {
        return in_array($this, [
            self::CALL,
            self::EMAIL,
            self::MEETING,
            self::DEMO,
            self::FOLLOW_UP,
        ]);
    }

    public function isSchedulable(): bool
    {
        return in_array($this, [
            self::CALL,
            self::MEETING,
            self::TASK,
            self::FOLLOW_UP,
            self::DEMO,
        ]);
    }

    public static function getOptions(): array
    {
        return collect(self::cases())->map(fn($type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'icon' => $type->icon(),
            'color' => $type->color(),
            'requires_outcome' => $type->requiresOutcome(),
            'is_schedulable' => $type->isSchedulable(),
        ])->toArray();
    }
}