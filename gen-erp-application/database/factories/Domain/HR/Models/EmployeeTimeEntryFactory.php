<?php

namespace Database\Factories\Domain\HR\Models;

use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\EmployeeTimeEntry;
use App\Domain\Project\Models\Project;
use App\Domain\Project\Models\Task;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\HR\Models\EmployeeTimeEntry>
 */
class EmployeeTimeEntryFactory extends Factory
{
    protected $model = EmployeeTimeEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $entryDate = fake()->dateTimeBetween('-30 days', 'now');
        $startTime = fake()->time('H:i');
        $hours = fake()->randomFloat(2, 0.5, 8);
        
        return [
            'employee_id' => Employee::factory(),
            'task_id' => Task::factory(),
            'project_id' => Project::factory(),
            'entry_date' => $entryDate,
            'start_time' => $startTime,
            'end_time' => null,
            'hours' => $hours,
            'description' => fake()->sentence(),
            'entry_type' => fake()->randomElement(['task', 'project', 'general', 'break', 'meeting']),
            'is_billable' => fake()->boolean(70),
            'is_approved' => false,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => true,
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function billable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_billable' => true,
        ]);
    }

    public function nonBillable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_billable' => false,
        ]);
    }
}
