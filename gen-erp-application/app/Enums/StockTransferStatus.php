<?php

namespace App\Enums;

enum StockTransferStatus: string
{
    case DRAFT = 'draft';
    case IN_TRANSIT = 'in_transit';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('Draft'),
            self::IN_TRANSIT => __('In Transit'),
            self::RECEIVED => __('Received'),
            self::CANCELLED => __('Cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'warning',
            self::IN_TRANSIT => 'info',
            self::RECEIVED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
