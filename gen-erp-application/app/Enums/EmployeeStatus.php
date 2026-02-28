<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case ACTIVE = 'active';
    case RESIGNED = 'resigned';
    case TERMINATED = 'terminated';
    case ON_LEAVE = 'on_leave';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => __('Active'),
            self::RESIGNED => __('Resigned'),
            self::TERMINATED => __('Terminated'),
            self::ON_LEAVE => __('On Leave'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::RESIGNED => 'warning',
            self::TERMINATED => 'danger',
            self::ON_LEAVE => 'info',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
