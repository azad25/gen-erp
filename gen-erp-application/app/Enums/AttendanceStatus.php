<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case LATE = 'late';
    case HALF_DAY = 'half_day';
    case HOLIDAY = 'holiday';
    case WEEKEND = 'weekend';
    case ON_LEAVE = 'on_leave';

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => __('Present'),
            self::ABSENT => __('Absent'),
            self::LATE => __('Late'),
            self::HALF_DAY => __('Half Day'),
            self::HOLIDAY => __('Holiday'),
            self::WEEKEND => __('Weekend'),
            self::ON_LEAVE => __('On Leave'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PRESENT => 'success',
            self::ABSENT => 'danger',
            self::LATE => 'warning',
            self::HALF_DAY => 'info',
            self::HOLIDAY => 'primary',
            self::WEEKEND => 'gray',
            self::ON_LEAVE => 'info',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
