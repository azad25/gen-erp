<?php

namespace Database\Factories\Domain\CRM\Models;

use App\Domain\CRM\Models\Pipeline;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\CRM\Models\Pipeline>
 */
class PipelineFactory extends Factory
{
    protected $model = Pipeline::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'company_id' => Company::factory(),
            'created_by' => User::factory(),
            'name' => $this->faker->words(2, true) . ' Pipeline',
            'description' => $this->faker->sentence(),
            'color' => $this->faker->hexColor(),
            'is_default' => false,
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 10),
            'settings' => null,
            'auto_move_stages' => $this->faker->boolean(20),
            'default_probability' => $this->faker->numberBetween(10, 30),
            'opportunities_count' => 0,
            'total_value' => 0,
            'won_value' => 0,
            'lost_value' => 0,
            'conversion_rate' => 0,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Default Sales Pipeline',
            'is_default' => true,
            'sort_order' => 1,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withMetrics(): static
    {
        return $this->state(fn (array $attributes) => [
            'opportunities_count' => $this->faker->numberBetween(5, 50),
            'total_value' => $this->faker->randomFloat(2, 10000, 500000),
            'won_value' => $this->faker->randomFloat(2, 1000, 100000),
            'lost_value' => $this->faker->randomFloat(2, 500, 50000),
            'conversion_rate' => $this->faker->randomFloat(2, 10, 40),
        ]);
    }
}