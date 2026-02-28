<?php

namespace App\Enums;

/**
 * Available custom field types with their Filament component and storage column mappings.
 */
enum CustomFieldType: string
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case NUMBER = 'number';
    case DECIMAL = 'decimal';
    case BOOLEAN = 'boolean';
    case DATE = 'date';
    case DATETIME = 'datetime';
    case SELECT = 'select';
    case MULTISELECT = 'multiselect';
    case URL = 'url';
    case EMAIL = 'email';
    case PHONE = 'phone';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => __('Text'),
            self::TEXTAREA => __('Text Area'),
            self::NUMBER => __('Number'),
            self::DECIMAL => __('Decimal'),
            self::BOOLEAN => __('Yes / No'),
            self::DATE => __('Date'),
            self::DATETIME => __('Date & Time'),
            self::SELECT => __('Dropdown'),
            self::MULTISELECT => __('Multi-Select'),
            self::URL => __('URL'),
            self::EMAIL => __('Email'),
            self::PHONE => __('Phone'),
        };
    }

    /**
     * Return the Filament field class name to use for form rendering.
     */
    public function filamentField(): string
    {
        return match ($this) {
            self::TEXT, self::URL, self::EMAIL, self::PHONE => \Filament\Forms\Components\TextInput::class,
            self::TEXTAREA => \Filament\Forms\Components\Textarea::class,
            self::NUMBER, self::DECIMAL => \Filament\Forms\Components\TextInput::class,
            self::BOOLEAN => \Filament\Forms\Components\Toggle::class,
            self::DATE => \Filament\Forms\Components\DatePicker::class,
            self::DATETIME => \Filament\Forms\Components\DateTimePicker::class,
            self::SELECT => \Filament\Forms\Components\Select::class,
            self::MULTISELECT => \Filament\Forms\Components\Select::class,
        };
    }

    /**
     * Which column in custom_field_values to store this type in.
     */
    public function storageColumn(): string
    {
        return match ($this) {
            self::TEXT, self::TEXTAREA, self::URL, self::EMAIL, self::PHONE, self::SELECT => 'value_text',
            self::NUMBER, self::DECIMAL => 'value_number',
            self::BOOLEAN => 'value_boolean',
            self::DATE, self::DATETIME => 'value_date',
            self::MULTISELECT => 'value_json',
        };
    }

    /**
     * Cast a raw input value for the correct storage column.
     */
    public function castForStorage(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($this) {
            self::TEXT, self::TEXTAREA, self::URL, self::EMAIL, self::PHONE, self::SELECT => (string) $value,
            self::NUMBER => (int) $value,
            self::DECIMAL => (float) $value,
            self::BOOLEAN => (bool) $value,
            self::DATE, self::DATETIME => $value,
            self::MULTISELECT => is_array($value) ? $value : [$value],
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
