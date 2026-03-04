<?php

namespace Database\Factories\Domain\Document\Models;

use App\Domain\Document\Models\FormField;
use App\Domain\Document\Models\Form;
use App\Support\Enums\FormFieldType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Document\Models\FormField>
 */
class FormFieldFactory extends Factory
{
    protected $model = FormField::class;

    public function definition(): array
    {
        $fieldTypes = [
            FormFieldType::TEXT->value,
            FormFieldType::EMAIL->value,
            FormFieldType::NUMBER->value,
            FormFieldType::SELECT->value,
            FormFieldType::TEXTAREA->value,
        ];

        $fieldType = $this->faker->randomElement($fieldTypes);
        $fieldKey = $this->faker->unique()->slug(2);

        return [
            'form_id' => Form::factory(),
            'field_key' => $fieldKey,
            'field_type' => $fieldType,
            'label' => $this->faker->words(2, true),
            'placeholder' => $this->faker->sentence(),
            'help_text' => $this->faker->sentence(),
            'is_required' => $this->faker->boolean(30),
            'validation_rules' => $this->getValidationRules($fieldType),
            'options' => $this->getOptions($fieldType),
            'settings' => [],
            'display_order' => $this->faker->numberBetween(0, 10),
            'is_active' => true,
        ];
    }

    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'field_type' => FormFieldType::TEXT->value,
            'validation_rules' => ['string', 'max:255'],
            'options' => null,
        ]);
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'field_type' => FormFieldType::EMAIL->value,
            'validation_rules' => ['email'],
            'options' => null,
        ]);
    }

    public function select(): static
    {
        return $this->state(fn (array $attributes) => [
            'field_type' => FormFieldType::SELECT->value,
            'validation_rules' => ['string'],
            'options' => ['Option 1', 'Option 2', 'Option 3'],
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

    private function getValidationRules(string $fieldType): array
    {
        return match ($fieldType) {
            FormFieldType::EMAIL->value => ['email'],
            FormFieldType::NUMBER->value, FormFieldType::INTEGER->value => ['numeric'],
            FormFieldType::URL->value => ['url'],
            FormFieldType::DATE->value => ['date'],
            FormFieldType::TIME->value => ['date_format:H:i'],
            FormFieldType::DATETIME->value => ['datetime'],
            default => ['string', 'max:255'],
        };
    }

    private function getOptions(string $fieldType): ?array
    {
        return match ($fieldType) {
            FormFieldType::SELECT->value,
            FormFieldType::MULTISELECT->value,
            FormFieldType::RADIO->value,
            FormFieldType::CHECKBOX->value => [
                'Option 1',
                'Option 2',
                'Option 3',
            ],
            default => null,
        };
    }
}