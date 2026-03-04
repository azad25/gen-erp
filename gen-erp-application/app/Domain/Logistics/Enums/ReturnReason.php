<?php

namespace App\Domain\Logistics\Enums;

enum ReturnReason: string
{
    case DAMAGED = 'damaged';
    case WRONG_ITEM = 'wrong_item';
    case NOT_NEEDED = 'not_needed';
    case QUALITY_ISSUE = 'quality_issue';
    case SIZE_ISSUE = 'size_issue';
    case LATE_DELIVERY = 'late_delivery';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::DAMAGED => 'Damaged Item',
            self::WRONG_ITEM => 'Wrong Item Received',
            self::NOT_NEEDED => 'No Longer Needed',
            self::QUALITY_ISSUE => 'Quality Issue',
            self::SIZE_ISSUE => 'Size Issue',
            self::LATE_DELIVERY => 'Late Delivery',
            self::OTHER => 'Other',
        };
    }

    public function requiresImages(): bool
    {
        return in_array($this, [self::DAMAGED, self::QUALITY_ISSUE, self::WRONG_ITEM]);
    }

    public function autoApprove(): bool
    {
        return in_array($this, [self::DAMAGED, self::WRONG_ITEM, self::LATE_DELIVERY]);
    }
}