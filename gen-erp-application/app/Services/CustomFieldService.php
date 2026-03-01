<?php

namespace App\Services;

use App\Enums\CustomFieldType;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Central service for managing custom field definitions and values.
 */
class CustomFieldService
{
    /**
     * Get all active field definitions for an entity type in the active company.
     *
     * @return Collection<int, CustomFieldDefinition>
     */
    public function getDefinitions(string $entityType): Collection
    {
        $companyId = CompanyContext::activeId();

        return Cache::remember(
            "custom_fields:{$companyId}:{$entityType}",
            3600,
            fn (): Collection => CustomFieldDefinition::query()
                ->active()
                ->forEntity($entityType)
                ->orderBy('display_order')
                ->get()
        );
    }

    /**
     * Get all field values for a specific entity record.
     *
     * @return Collection<int, CustomFieldValue>
     */
    public function getValues(string $entityType, int $entityId): Collection
    {
        return CustomFieldValue::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->get()
            ->keyBy('field_key');
    }

    /**
     * Save field values for an entity record (upsert pattern).
     *
     * @param  array<string, mixed>  $data
     */
    public function saveValues(string $entityType, int $entityId, array $data): void
    {
        $companyId = CompanyContext::activeId();
        $definitions = $this->getDefinitions($entityType)->keyBy('field_key');

        foreach ($data as $fieldKey => $rawValue) {
            $definition = $definitions->get($fieldKey);

            if (! $definition) {
                continue;
            }

            $castedValue = $definition->castValue($rawValue);
            $storageColumn = $definition->field_type->storageColumn();

            // Reset all value columns to null, then set the correct one
            $valueData = [
                'value_text' => null,
                'value_number' => null,
                'value_boolean' => null,
                'value_date' => null,
                'value_json' => null,
            ];
            $valueData[$storageColumn] = $castedValue;

            CustomFieldValue::withoutGlobalScopes()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'field_key' => $fieldKey,
                ],
                $valueData,
            );
        }
    }

    /**
     * Build validation rules for all active custom fields of an entity type.
     *
     * @return array<string, array<int, mixed>>
     */
    public function buildValidationRules(string $entityType): array
    {
        $definitions = $this->getDefinitions($entityType);
        $rules = [];

        foreach ($definitions as $definition) {
            $rules["custom_fields.{$definition->field_key}"] = $definition->buildValidationRule();
        }

        return $rules;
    }

    /**
     * Get a custom field value for a record using its HasCustomFields trait.
     */
    private function getValueForRecord(mixed $record, CustomFieldDefinition $definition): mixed
    {
        if (method_exists($record, 'getCustomField')) {
            return $record->getCustomField($definition->field_key);
        }

        // Fallback: query directly
        $value = CustomFieldValue::withoutGlobalScopes()
            ->where('company_id', CompanyContext::activeId())
            ->where('entity_type', $definition->entity_type)
            ->where('entity_id', $record->getKey())
            ->where('field_key', $definition->field_key)
            ->first();

        return $value?->getTypedValue($definition->field_type);
    }
}
