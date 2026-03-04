<?php

namespace App\Support\Enums;

/**
 * Available form field types for dynamic form building.
 */
enum FormFieldType: string
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case EMAIL = 'email';
    case URL = 'url';
    case PHONE = 'phone';
    case NUMBER = 'number';
    case INTEGER = 'integer';
    case DATE = 'date';
    case DATETIME = 'datetime';
    case TIME = 'time';
    case SELECT = 'select';
    case MULTISELECT = 'multiselect';
    case RADIO = 'radio';
    case CHECKBOX = 'checkbox';
    case BOOLEAN = 'boolean';
    case FILE = 'file';
    case IMAGE = 'image';
    case RICH_TEXT = 'rich_text';
    case SIGNATURE = 'signature';
    case RATING = 'rating';
    case SLIDER = 'slider';
    case COLOR = 'color';
    case HIDDEN = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => __('Text Input'),
            self::TEXTAREA => __('Text Area'),
            self::EMAIL => __('Email'),
            self::URL => __('URL'),
            self::PHONE => __('Phone Number'),
            self::NUMBER => __('Number'),
            self::INTEGER => __('Integer'),
            self::DATE => __('Date'),
            self::DATETIME => __('Date & Time'),
            self::TIME => __('Time'),
            self::SELECT => __('Dropdown'),
            self::MULTISELECT => __('Multi-Select'),
            self::RADIO => __('Radio Buttons'),
            self::CHECKBOX => __('Checkboxes'),
            self::BOOLEAN => __('Yes/No'),
            self::FILE => __('File Upload'),
            self::IMAGE => __('Image Upload'),
            self::RICH_TEXT => __('Rich Text Editor'),
            self::SIGNATURE => __('Signature'),
            self::RATING => __('Rating'),
            self::SLIDER => __('Slider'),
            self::COLOR => __('Color Picker'),
            self::HIDDEN => __('Hidden Field'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TEXT => 'text-fields',
            self::TEXTAREA => 'subject',
            self::EMAIL => 'email',
            self::URL => 'link',
            self::PHONE => 'phone',
            self::NUMBER, self::INTEGER => 'numbers',
            self::DATE => 'calendar-today',
            self::DATETIME => 'schedule',
            self::TIME => 'access-time',
            self::SELECT => 'arrow-drop-down',
            self::MULTISELECT => 'checklist',
            self::RADIO => 'radio-button-checked',
            self::CHECKBOX => 'check-box',
            self::BOOLEAN => 'toggle-on',
            self::FILE => 'attach-file',
            self::IMAGE => 'image',
            self::RICH_TEXT => 'format-bold',
            self::SIGNATURE => 'draw',
            self::RATING => 'star',
            self::SLIDER => 'tune',
            self::COLOR => 'palette',
            self::HIDDEN => 'visibility-off',
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::TEXT, self::TEXTAREA, self::EMAIL, self::URL, self::PHONE => 'text',
            self::NUMBER, self::INTEGER, self::SLIDER => 'numeric',
            self::DATE, self::DATETIME, self::TIME => 'date',
            self::SELECT, self::MULTISELECT, self::RADIO, self::CHECKBOX, self::BOOLEAN => 'selection',
            self::FILE, self::IMAGE => 'media',
            self::RICH_TEXT, self::SIGNATURE => 'advanced',
            self::RATING, self::COLOR => 'interactive',
            self::HIDDEN => 'system',
        };
    }

    public function hasOptions(): bool
    {
        return in_array($this, [
            self::SELECT,
            self::MULTISELECT,
            self::RADIO,
            self::CHECKBOX,
        ]);
    }

    public function supportsValidation(): bool
    {
        return !in_array($this, [
            self::HIDDEN,
            self::SIGNATURE,
        ]);
    }

    public function getDefaultSettings(): array
    {
        return match ($this) {
            self::TEXT, self::EMAIL, self::URL, self::PHONE => [
                'maxLength' => 255,
                'minLength' => null,
            ],
            self::TEXTAREA => [
                'maxLength' => 10000,
                'minLength' => null,
                'rows' => 4,
            ],
            self::NUMBER, self::INTEGER => [
                'min' => null,
                'max' => null,
                'step' => self::INTEGER === $this ? 1 : 0.01,
            ],
            self::SLIDER => [
                'min' => 0,
                'max' => 100,
                'step' => 1,
            ],
            self::RATING => [
                'max' => 5,
                'allowHalf' => false,
            ],
            self::FILE => [
                'maxSize' => 10240, // 10MB in KB
                'allowedTypes' => ['pdf', 'doc', 'docx', 'txt'],
            ],
            self::IMAGE => [
                'maxSize' => 2048, // 2MB in KB
                'allowedTypes' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            ],
            self::RICH_TEXT => [
                'toolbar' => ['bold', 'italic', 'underline', 'link', 'bulletList', 'orderedList'],
            ],
            default => [],
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

    /**
     * Get field types grouped by category.
     */
    public static function groupedOptions(): array
    {
        $grouped = [];
        
        foreach (self::cases() as $case) {
            $category = $case->category();
            $grouped[$category][] = [
                'value' => $case->value,
                'label' => $case->label(),
                'icon' => $case->icon(),
            ];
        }
        
        return $grouped;
    }
}