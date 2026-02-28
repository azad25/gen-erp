<?php

namespace App\Enums;

enum CreditNoteStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case APPLIED = 'applied';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('Draft'),
            self::ISSUED => __('Issued'),
            self::APPLIED => __('Applied'),
            self::CANCELLED => __('Cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::ISSUED => 'info',
            self::APPLIED => 'success',
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
