<?php

namespace Database\Factories\Domain\Shared\Models;

use App\Domain\Shared\Models\CustomFieldDefinition;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Support\Enums\CustomFieldType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Shared\Models\CustomFieldDefinition>
 */
class CustomFieldDefinitionFactory extends Factory
{
    protected $model = CustomFieldDefinition::class;

    public function definition(): array
    {
        $fieldTypes = [
            CustomFieldType::TEXT->value,
            CustomFieldType::EMAIL->value,
            CustomFieldType::NUMBER->value,
            CustomFieldType::SELECT->value,
            CustomFieldType::TEXTAREA->value,
            CustomFieldType::BOOLEAN->value,
            CustomFieldType::DATE->value,
        ];

        $domains = ['sales', 'purchase', 'inventory', 'accounting', 'hr', 'crm'];
        $entityTypes = ['customer', 'supplier', 'product', 'order', 'invoice', 'employee'];

        $fieldType = $this->faker->randomElement($fieldTypes);
        $domain = $this->faker->randomElement($domains);
        $entityType = $this->faker->randomElement($entityTypes);

        return [
            'company_id' => Company::factory(),
            'domain' => $domain,
            'entity_type' => $entityType,
            'field_key' => $this->faker->unique()->slug(2),
            'label' => $this->faker->words(2, true),
            'field_type' => $fieldType,
            'help_text' => $this->faker->sentence(),
            'is_required' => $this->faker->boolean(30),
            'is_filterable' => $this->faker->boolean(20),
            'is_searchable' => $this->faker->boolean(20),
            'show_in_list' => $this->faker->boolean(40),
            'default_value' => null,
            'options' => $this->getOptions($fieldType),
            'validation_rules' => $this->getValidationRules($fieldType),
            'conditional_logic' => null,
            'display_order' => $this->faker->numberBetween(0, 10),
            'is_active' => true,
            'created_by' => User::factory(),
            'security_level' => $this->faker->randomElement(['public', 'internal', 'restricted']),
        ];
    }

    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'field_type' => CustomFieldType::TEXT->value,
            'validation_rules' => ['string', 'max:255'],
            'options' => null,
        ]);
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'field_type' => CustomFieldType::EMAIL->value,
            'validation_rules' => ['email'],
            'options' => null,
        ]);
    }

    public function select(): static
    {
        return $this->state(fn (array $attributes) => [
            'field_type' => CustomFieldType::SELECT->value,
            'validation_rules' => ['string'],
            'options' => ['Option 1', 'Option 2', 'Option 3'],
        ]);
    }

    public function boolean(): static
    {
        return $this->state(fn (array $attributes) => [
            'field_type' => CustomFieldType::BOOLEAN->value,
            'validation_rules' => ['boolean'],
            'options' => null,
        ]);
    }

    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }

    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => false,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function forDomain(string $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => $domain,
        ]);
    }

    public function forEntity(string $entityType): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => $entityType,
        ]);
    }

    public function filterable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_filterable' => true,
        ]);
    }

    public function searchable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_searchable' => true,
        ]);
    }

    private function getValidationRules(string $fieldType): array
    {
        return match ($fieldType) {
            CustomFieldType::EMAIL->value => ['email'],
            CustomFieldType::NUMBER->value => ['numeric'],
            CustomFieldType::DECIMAL->value => ['numeric'],
            CustomFieldType::URL->value => ['url'],
            CustomFieldType::DATE->value => ['date'],
            CustomFieldType::DATETIME->value => ['datetime'],
            CustomFieldType::BOOLEAN->value => ['boolean'],
            default => ['string', 'max:255'],
        };
    }

    private function getOptions(string $fieldType): ?array
    {
        return match ($fieldType) {
            CustomFieldType::SELECT->value,
            CustomFieldType::MULTISELECT->value => [
                'Option 1',
                'Option 2',
                'Option 3',
            ],
            default => null,
        };
    }
}