<?php

namespace Database\Factories;

use App\Enums\WorkflowDocumentType;
use App\Models\Company;
use App\Models\WorkflowDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowDefinition>
 */
class WorkflowDefinitionFactory extends Factory
{
    protected $model = WorkflowDefinition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'document_type' => WorkflowDocumentType::PURCHASE_ORDER->value,
            'name' => fake()->words(3, true),
            'is_active' => true,
            'is_default' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
