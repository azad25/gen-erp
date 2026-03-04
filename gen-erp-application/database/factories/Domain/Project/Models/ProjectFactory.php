<?php

namespace Database\Factories\Domain\Project\Models;

use App\Domain\Project\Models\Project;
use App\Domain\Auth\Models\Company;
use App\Domain\HR\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement([
                Project::STATUS_PLANNING,
                Project::STATUS_ACTIVE,
                Project::STATUS_ON_HOLD,
                Project::STATUS_COMPLETED,
                Project::STATUS_CANCELLED
            ]),
            'priority' => $this->faker->randomElement([
                Project::PRIORITY_LOW,
                Project::PRIORITY_MEDIUM,
                Project::PRIORITY_HIGH,
                Project::PRIORITY_URGENT
            ]),
            'start_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+3 months'),
            'budget' => $this->faker->randomFloat(2, 1000, 100000),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'BDT']),
            'client_name' => $this->faker->company(),
            'client_email' => $this->faker->companyEmail(),
            'client_phone' => $this->faker->phoneNumber(),
            'project_manager_id' => null, // Will be set by relationships
            'is_billable' => $this->faker->boolean(80),
            'hourly_rate' => $this->faker->randomFloat(2, 25, 150),
            'estimated_hours' => $this->faker->numberBetween(40, 1000),
            'actual_hours' => 0,
            'progress_percentage' => $this->faker->numberBetween(0, 100),
            'color' => $this->faker->hexColor(),
            'settings' => [],
        ];
    }

    public function withProjectManager(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'project_manager_id' => Employee::factory()->create([
                    'company_id' => $attributes['company_id']
                ])->id,
            ];
        });
    }

    public function planning(): static
    {
        return $this->state([
            'status' => Project::STATUS_PLANNING,
            'progress_percentage' => 0,
        ]);
    }

    public function active(): static
    {
        return $this->state([
            'status' => Project::STATUS_ACTIVE,
            'progress_percentage' => $this->faker->numberBetween(1, 99),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => Project::STATUS_COMPLETED,
            'progress_percentage' => 100,
        ]);
    }

    public function overdue(): static
    {
        return $this->state([
            'end_date' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
            'status' => $this->faker->randomElement([
                Project::STATUS_ACTIVE,
                Project::STATUS_ON_HOLD
            ]),
        ]);
    }

    public function highPriority(): static
    {
        return $this->state([
            'priority' => Project::PRIORITY_HIGH,
        ]);
    }

    public function urgent(): static
    {
        return $this->state([
            'priority' => Project::PRIORITY_URGENT,
        ]);
    }
}