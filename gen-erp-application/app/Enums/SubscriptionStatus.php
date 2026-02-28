<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case TRIALING = 'trialing';
    case GRACE = 'grace';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::TRIALING => 'Trial',
            self::GRACE => 'Grace Period',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function isAccessible(): bool
    {
        return in_array($this, [self::ACTIVE, self::TRIALING, self::GRACE], true);
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::TRIALING => 'info',
            self::GRACE => 'warning',
            self::EXPIRED => 'danger',
            self::CANCELLED => 'gray',
        };
    }
}
