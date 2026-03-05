<?php

namespace Database\Factories\Domain\HR\Models;

use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\EmployeeTask;
use App\Domain\Project\Models\Project;
use App\Domain\Project\Models\Task;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\HR\Models\EmployeeTask>
 */
class EmployeeTaskFactory extends Factory
{
    protected $model = EmployeeTask::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'task_id' => Task::factory(),
            'project_id' => Project::factory(),
            'assigned_by' => User::factory(),
            'assigned_at' => now(),
            'status' => fake()->randomElement(['assigned', 'in_progress', 'completed', 'on_hold']),
            'estimated_hours' => fake()->randomFloat(2, 1, 40),
            'actual_hours' => fake()->randomFloat(2, 0, 40),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'assigned',
            'started_at' => null,
            'completed_at' => null,
            'actual_hours' => 0,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'started_at' => now()->subHours(fake()->numberBetween(1, 8)),
            'completed_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subDays(fake()->numberBetween(1, 7)),
            'completed_at' => now()->subDays(fake()->numberBetween(0, 3)),
        ]);
    }
}
