<?php

namespace App\Models;

use App\Enums\CustomFieldType;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Defines a custom field for a specific entity type within a company.
 */
class CustomFieldDefinition extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'entity_type',
        'field_key',
        'label',
        'field_type',
        'is_required',
        'is_filterable',
        'is_searchable',
        'show_in_list',
        'default_value',
        'options',
        'validation_rules',
        'display_order',
        'is_active',
        'generated_column_name',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'field_type' => CustomFieldType::class,
            'options' => 'array',
            'validation_rules' => 'array',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'is_searchable' => 'boolean',
            'show_in_list' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    // ── Scopes ───────────────────────────────────────────────

    /**
     * @param  Builder<CustomFieldDefinition>  $query
     * @return Builder<CustomFieldDefinition>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<CustomFieldDefinition>  $query
     * @return Builder<CustomFieldDefinition>
     */
    public function scopeForEntity(Builder $query, string $entityType): Builder
    {
        return $query->where('entity_type', $entityType);
    }

    // ── Methods ──────────────────────────────────────────────

    /**
     * Build a Laravel validation rule array for this field.
     *
     * @return array<int, mixed>
     */
    public function buildValidationRule(): array
    {
        $rules = [];

        $rules[] = $this->is_required ? 'required' : 'nullable';

        $rules = array_merge($rules, match ($this->field_type) {
            CustomFieldType::TEXT, CustomFieldType::TEXTAREA => ['string', 'max:10000'],
            CustomFieldType::NUMBER => ['integer'],
            CustomFieldType::DECIMAL => ['numeric'],
            CustomFieldType::BOOLEAN => ['boolean'],
            CustomFieldType::DATE => ['date'],
            CustomFieldType::DATETIME => ['date'],
            CustomFieldType::SELECT => ['string', 'max:255'],
            CustomFieldType::MULTISELECT => ['array'],
            CustomFieldType::URL => ['url', 'max:2000'],
            CustomFieldType::EMAIL => ['email', 'max:255'],
            CustomFieldType::PHONE => ['string', 'max:20', 'regex:/^01[3-9]\d{8}$/'],
        });

        // Merge any additional validation rules defined at the field level
        if (! empty($this->validation_rules)) {
            $rules = array_merge($rules, $this->validation_rules);
        }

        return $rules;
    }

    /**
     * Cast a raw input value to the correct type for storage.
     */
    public function castValue(mixed $raw): mixed
    {
        return $this->field_type->castForStorage($raw);
    }
}
