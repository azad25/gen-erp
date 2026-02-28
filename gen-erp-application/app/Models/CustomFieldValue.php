<?php

namespace App\Models;

use App\Enums\CustomFieldType;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores a single custom field value for a specific entity record.
 */
class CustomFieldValue extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'entity_type',
        'entity_id',
        'field_key',
        'value_text',
        'value_number',
        'value_boolean',
        'value_date',
        'value_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'value_number' => 'decimal:4',
            'value_boolean' => 'boolean',
            'value_date' => 'date',
            'value_json' => 'array',
        ];
    }

    /**
     * Return the value from the correct column based on the field's type.
     */
    public function getTypedValue(?CustomFieldType $fieldType = null): mixed
    {
        if (! $fieldType) {
            return $this->value_text
                ?? $this->value_number
                ?? $this->value_boolean
                ?? $this->value_date
                ?? $this->value_json;
        }

        $column = $fieldType->storageColumn();

        return $this->{$column};
    }
}
