<?php

namespace Database\Factories;

use App\Enums\CustomFieldType;
use App\Models\Company;
use App\Models\CustomFieldDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomFieldDefinition>
 */
class CustomFieldDefinitionFactory extends Factory
{
    protected $model = CustomFieldDefinition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'entity_type' => 'product',
            'field_key' => fake()->unique()->slug(2),
            'label' => fake()->words(2, true),
            'field_type' => CustomFieldType::TEXT->value,
            'is_required' => false,
            'is_filterable' => false,
            'is_searchable' => false,
            'show_in_list' => false,
            'display_order' => 0,
            'is_active' => true,
        ];
    }

    public function required(): static
    {
        return $this->state(fn (): array => ['is_required' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function filterable(): static
    {
        return $this->state(fn (): array => ['is_filterable' => true]);
    }

    public function showInList(): static
    {
        return $this->state(fn (): array => ['show_in_list' => true]);
    }

    public function select(array $options = []): static
    {
        return $this->state(fn (): array => [
            'field_type' => CustomFieldType::SELECT->value,
            'options' => $options ?: [
                ['value' => 'a', 'label' => 'Option A'],
                ['value' => 'b', 'label' => 'Option B'],
            ],
        ]);
    }
}
