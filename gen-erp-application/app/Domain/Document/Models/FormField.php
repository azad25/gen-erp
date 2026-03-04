<?php

namespace App\Domain\Document\Models;

use App\Support\Enums\FormFieldType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Individual field within a form.
 */
class FormField extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'field_key',
        'field_type',
        'label',
        'placeholder',
        'help_text',
        'is_required',
        'validation_rules',
        'options',
        'settings',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'field_type' => FormFieldType::class,
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'validation_rules' => 'array',
            'options' => 'array',
            'settings' => 'array',
            'display_order' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    /**
     * @return BelongsTo<Form, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Methods ──────────────────────────────────────────────

    /**
     * Get validation rules specific to field type.
     */
    public function getTypeValidationRules(): array
    {
        return match ($this->field_type) {
            FormFieldType::EMAIL => ['email'],
            FormFieldType::URL => ['url'],
            FormFieldType::NUMBER => ['numeric'],
            FormFieldType::INTEGER => ['integer'],
            FormFieldType::DATE => ['date'],
            FormFieldType::DATETIME => ['date'],
            FormFieldType::TIME => ['date_format:H:i'],
            FormFieldType::PHONE => ['regex:/^01[3-9]\d{8}$/'],
            FormFieldType::FILE => ['file'],
            FormFieldType::IMAGE => ['image', 'max:2048'], // 2MB max
            FormFieldType::SELECT, FormFieldType::RADIO => $this->getSelectValidationRules(),
            FormFieldType::MULTISELECT, FormFieldType::CHECKBOX => ['array'],
            default => ['string', 'max:10000'],
        };
    }

    /**
     * Get validation rules for select/radio fields.
     */
    private function getSelectValidationRules(): array
    {
        if (empty($this->options)) {
            return ['string'];
        }

        $validValues = collect($this->options)->pluck('value')->toArray();
        return ['string', 'in:' . implode(',', $validValues)];
    }

    /**
     * Get field configuration for frontend rendering.
     */
    public function getFieldConfig(): array
    {
        return [
            'key' => $this->field_key,
            'type' => $this->field_type->value,
            'label' => $this->label,
            'placeholder' => $this->placeholder,
            'helpText' => $this->help_text,
            'required' => $this->is_required,
            'options' => $this->options,
            'settings' => $this->settings ?? [],
            'validation' => $this->getTypeValidationRules(),
        ];
    }

    /**
     * Validate field value.
     */
    public function validateValue($value): bool
    {
        $rules = $this->getTypeValidationRules();
        
        if ($this->is_required && (is_null($value) || $value === '')) {
            return false;
        }
        
        // Add custom validation logic here
        return true;
    }
}