<?php

namespace App\Enums;

enum EmploymentType: string
{
    case PERMANENT = 'permanent';
    case PROBATION = 'probation';
    case CONTRACT = 'contract';
    case PART_TIME = 'part_time';
    case INTERN = 'intern';

    public function label(): string
    {
        return match ($this) {
            self::PERMANENT => __('Permanent'),
            self::PROBATION => __('Probation'),
            self::CONTRACT => __('Contract'),
            self::PART_TIME => __('Part Time'),
            self::INTERN => __('Intern'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PERMANENT => 'success',
            self::PROBATION => 'warning',
            self::CONTRACT => 'info',
            self::PART_TIME => 'gray',
            self::INTERN => 'primary',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
