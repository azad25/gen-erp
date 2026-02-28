<?php

namespace App\Services;

use App\Enums\CustomFieldType;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Central service for managing custom field definitions, values, and UI components.
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
     * Build Filament form schema components for an entity type's custom fields.
     *
     * @return array<int, \Filament\Forms\Components\Component>
     */
    public function buildFormComponents(string $entityType): array
    {
        $definitions = $this->getDefinitions($entityType);

        if ($definitions->isEmpty()) {
            return [];
        }

        $fields = [];

        foreach ($definitions as $definition) {
            $field = $this->buildSingleFormComponent($definition);

            if ($field) {
                $fields[] = $field;
            }
        }

        if (empty($fields)) {
            return [];
        }

        return [
            Section::make(__('Custom Fields'))
                ->schema($fields)
                ->collapsed(),
        ];
    }

    /**
     * Build Filament table columns for fields marked show_in_list.
     *
     * @return array<int, \Filament\Tables\Columns\Column>
     */
    public function buildTableColumns(string $entityType): array
    {
        $definitions = $this->getDefinitions($entityType)
            ->where('show_in_list', true);

        $columns = [];

        foreach ($definitions as $definition) {
            if ($definition->field_type === CustomFieldType::BOOLEAN) {
                $columns[] = IconColumn::make("cf_{$definition->field_key}")
                    ->label($definition->label)
                    ->boolean()
                    ->getStateUsing(fn ($record): ?bool => $this->getValueForRecord($record, $definition))
                    ->sortable(false);
            } else {
                $columns[] = TextColumn::make("cf_{$definition->field_key}")
                    ->label($definition->label)
                    ->getStateUsing(fn ($record): mixed => $this->getValueForRecord($record, $definition))
                    ->sortable(false)
                    ->searchable(false);
            }
        }

        return $columns;
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
     * Build a single Filament form component from a custom field definition.
     */
    private function buildSingleFormComponent(CustomFieldDefinition $definition): ?\Filament\Forms\Components\Component
    {
        $fieldName = "custom_fields.{$definition->field_key}";

        $component = match ($definition->field_type) {
            CustomFieldType::TEXT, CustomFieldType::URL, CustomFieldType::EMAIL, CustomFieldType::PHONE => TextInput::make($fieldName)
                ->label($definition->label)
                ->required($definition->is_required)
                ->default($definition->default_value)
                ->maxLength(10000),
            CustomFieldType::TEXTAREA => Textarea::make($fieldName)
                ->label($definition->label)
                ->required($definition->is_required)
                ->default($definition->default_value),
            CustomFieldType::NUMBER => TextInput::make($fieldName)
                ->label($definition->label)
                ->required($definition->is_required)
                ->numeric()
                ->default($definition->default_value),
            CustomFieldType::DECIMAL => TextInput::make($fieldName)
                ->label($definition->label)
                ->required($definition->is_required)
                ->numeric()
                ->step(0.0001)
                ->default($definition->default_value),
            CustomFieldType::BOOLEAN => Toggle::make($fieldName)
                ->label($definition->label)
                ->required($definition->is_required)
                ->default((bool) $definition->default_value),
            CustomFieldType::DATE => DatePicker::make($fieldName)
                ->label($definition->label)
                ->required($definition->is_required)
                ->default($definition->default_value),
            CustomFieldType::DATETIME => DateTimePicker::make($fieldName)
                ->label($definition->label)
                ->required($definition->is_required)
                ->default($definition->default_value),
            CustomFieldType::SELECT => Select::make($fieldName)
                ->label($definition->label)
                ->required($definition->is_required)
                ->options($this->parseSelectOptions($definition->options))
                ->default($definition->default_value),
            CustomFieldType::MULTISELECT => Select::make($fieldName)
                ->label($definition->label)
                ->required($definition->is_required)
                ->multiple()
                ->options($this->parseSelectOptions($definition->options))
                ->default($definition->default_value),
        };

        return $component;
    }

    /**
     * Parse select options from the JSON field.
     *
     * @param  array<int, array{value: string, label: string}>|null  $options
     * @return array<string, string>
     */
    private function parseSelectOptions(?array $options): array
    {
        if (empty($options)) {
            return [];
        }

        return collect($options)
            ->mapWithKeys(fn (array $opt): array => [$opt['value'] => $opt['label']])
            ->all();
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
