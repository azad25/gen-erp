<?php

namespace App\Domain\Logistics\Enums;

enum ShipmentStatus: string
{
    case PENDING = 'pending';
    case PICKUP_SCHEDULED = 'pickup_scheduled';
    case PICKED_UP = 'picked_up';
    case IN_TRANSIT = 'in_transit';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';
    case RETURNED = 'returned';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PICKUP_SCHEDULED => 'Pickup Scheduled',
            self::PICKED_UP => 'Picked Up',
            self::IN_TRANSIT => 'In Transit',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED => 'Delivered',
            self::FAILED => 'Failed',
            self::RETURNED => 'Returned',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::PICKUP_SCHEDULED => 'info',
            self::PICKED_UP => 'info',
            self::IN_TRANSIT => 'info',
            self::OUT_FOR_DELIVERY => 'primary',
            self::DELIVERED => 'success',
            self::FAILED => 'danger',
            self::RETURNED => 'warning',
            self::CANCELLED => 'secondary',
        };
    }

    public function isCompleted(): bool
    {
        return in_array($this, [self::DELIVERED, self::RETURNED, self::CANCELLED]);
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }
}