<?php

namespace App\Enums;

/**
 * Alert rule comparison operators.
 */
enum AlertOperator: string
{
    case LT = 'lt';
    case LTE = 'lte';
    case GT = 'gt';
    case GTE = 'gte';
    case EQ = 'eq';
    case NEQ = 'neq';
    case CONTAINS = 'contains';
    case NOT_CONTAINS = 'not_contains';
    case IS_NULL = 'is_null';
    case NOT_NULL = 'not_null';

    public function label(): string
    {
        return match ($this) {
            self::LT => __('Less than'),
            self::LTE => __('Less than or equal'),
            self::GT => __('Greater than'),
            self::GTE => __('Greater than or equal'),
            self::EQ => __('Equals'),
            self::NEQ => __('Not equals'),
            self::CONTAINS => __('Contains'),
            self::NOT_CONTAINS => __('Does not contain'),
            self::IS_NULL => __('Is empty'),
            self::NOT_NULL => __('Is not empty'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
