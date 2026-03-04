<?php

namespace Database\Factories\Domain\CRM\Models;

use App\Domain\CRM\Models\Pipeline;
use App\Domain\CRM\Models\PipelineStage;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\CRM\Models\PipelineStage>
 */
class PipelineStageFactory extends Factory
{
    protected $model = PipelineStage::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'company_id' => Company::factory(),
            'pipeline_id' => Pipeline::factory(),
            'created_by' => User::factory(),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'color' => $this->faker->hexColor(),
            'sort_order' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'probability' => $this->faker->numberBetween(10, 90),
            'is_closed_won' => false,
            'is_closed_lost' => false,
            'requires_reason' => $this->faker->boolean(30),
            'entry_actions' => null,
            'exit_actions' => null,
            'max_days_in_stage' => $this->faker->boolean(40) ? $this->faker->numberBetween(7, 30) : null,
            'opportunities_count' => 0,
            'total_value' => 0,
            'average_days' => 0,
            'conversion_rate' => 0,
        ];
    }

    public function prospecting(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Prospecting',
            'probability' => 10,
            'sort_order' => 1,
            'color' => '#6B7280',
        ]);
    }

    public function qualification(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Qualification',
            'probability' => 25,
            'sort_order' => 2,
            'color' => '#3B82F6',
        ]);
    }

    public function needsAnalysis(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Needs Analysis',
            'probability' => 50,
            'sort_order' => 3,
            'color' => '#F59E0B',
        ]);
    }

    public function proposal(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Proposal',
            'probability' => 75,
            'sort_order' => 4,
            'color' => '#8B5CF6',
        ]);
    }

    public function negotiation(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Negotiation',
            'probability' => 90,
            'sort_order' => 5,
            'color' => '#EF4444',
        ]);
    }

    public function closedWon(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Closed Won',
            'probability' => 100,
            'sort_order' => 6,
            'color' => '#10B981',
            'is_closed_won' => true,
        ]);
    }

    public function closedLost(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Closed Lost',
            'probability' => 0,
            'sort_order' => 7,
            'color' => '#6B7280',
            'is_closed_lost' => true,
        ]);
    }

    public function withMetrics(): static
    {
        return $this->state(fn (array $attributes) => [
            'opportunities_count' => $this->faker->numberBetween(1, 20),
            'total_value' => $this->faker->randomFloat(2, 5000, 200000),
            'average_days' => $this->faker->randomFloat(2, 3, 30),
            'conversion_rate' => $this->faker->randomFloat(2, 10, 80),
        ]);
    }
}