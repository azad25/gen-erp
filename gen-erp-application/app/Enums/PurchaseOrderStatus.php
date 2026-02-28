<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case PARTIAL = 'partial';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('Draft'),
            self::SENT => __('Sent'),
            self::PARTIAL => __('Partially Received'),
            self::RECEIVED => __('Received'),
            self::CANCELLED => __('Cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'info',
            self::PARTIAL => 'warning',
            self::RECEIVED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s): array => [$s->value => $s->label()])
            ->all();
    }
}
