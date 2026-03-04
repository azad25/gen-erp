<?php

namespace Database\Factories\Domain\Project\Models;

use App\Domain\Project\Models\Task;
use App\Domain\Project\Models\Project;
use App\Domain\Project\Models\BoardColumn;
use App\Domain\HR\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'board_id' => null, // Will be set by relationships
            'board_column_id' => null, // Will be set by relationships
            'parent_task_id' => null,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement([
                Task::STATUS_TODO,
                Task::STATUS_IN_PROGRESS,
                Task::STATUS_IN_REVIEW,
                Task::STATUS_TESTING,
                Task::STATUS_COMPLETED,
                Task::STATUS_CANCELLED
            ]),
            'priority' => $this->faker->randomElement([
                Task::PRIORITY_LOW,
                Task::PRIORITY_MEDIUM,
                Task::PRIORITY_HIGH,
                Task::PRIORITY_URGENT
            ]),
            'type' => $this->faker->randomElement([
                Task::TYPE_TASK,
                Task::TYPE_BUG,
                Task::TYPE_FEATURE,
                Task::TYPE_IMPROVEMENT,
                Task::TYPE_EPIC,
                Task::TYPE_STORY
            ]),
            'assignee_id' => null, // Will be set by relationships
            'reporter_id' => null, // Will be set by relationships
            'start_date' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+2 months'),
            'estimated_hours' => $this->faker->optional()->randomFloat(1, 1, 40),
            'actual_hours' => 0,
            'story_points' => $this->faker->optional()->numberBetween(1, 13),
            'position' => $this->faker->numberBetween(1, 100),
            'tags' => $this->faker->optional()->randomElements([
                'frontend', 'backend', 'api', 'ui', 'database', 'testing', 'documentation'
            ], $this->faker->numberBetween(0, 3)),
            'settings' => [],
        ];
    }

    public function withAssignee(): static
    {
        return $this->state(function (array $attributes) {
            $project = Project::find($attributes['project_id']);
            return [
                'assignee_id' => Employee::factory()->create([
                    'company_id' => $project->company_id
                ])->id,
            ];
        });
    }

    public function withReporter(): static
    {
        return $this->state(function (array $attributes) {
            $project = Project::find($attributes['project_id']);
            return [
                'reporter_id' => Employee::factory()->create([
                    'company_id' => $project->company_id
                ])->id,
            ];
        });
    }

    public function todo(): static
    {
        return $this->state([
            'status' => Task::STATUS_TODO,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state([
            'status' => Task::STATUS_IN_PROGRESS,
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => Task::STATUS_COMPLETED,
        ]);
    }

    public function overdue(): static
    {
        return $this->state([
            'due_date' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
            'status' => $this->faker->randomElement([
                Task::STATUS_TODO,
                Task::STATUS_IN_PROGRESS,
                Task::STATUS_IN_REVIEW
            ]),
        ]);
    }

    public function dueToday(): static
    {
        return $this->state([
            'due_date' => now()->format('Y-m-d'),
        ]);
    }

    public function dueThisWeek(): static
    {
        return $this->state([
            'due_date' => $this->faker->dateTimeBetween('now', '+1 week'),
        ]);
    }

    public function highPriority(): static
    {
        return $this->state([
            'priority' => Task::PRIORITY_HIGH,
        ]);
    }

    public function urgent(): static
    {
        return $this->state([
            'priority' => Task::PRIORITY_URGENT,
        ]);
    }

    public function bug(): static
    {
        return $this->state([
            'type' => Task::TYPE_BUG,
            'priority' => $this->faker->randomElement([
                Task::PRIORITY_HIGH,
                Task::PRIORITY_URGENT
            ]),
        ]);
    }

    public function feature(): static
    {
        return $this->state([
            'type' => Task::TYPE_FEATURE,
        ]);
    }

    public function subtask(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'parent_task_id' => Task::factory()->create([
                    'project_id' => $attributes['project_id'],
                    'board_column_id' => $attributes['board_column_id'],
                ])->id,
            ];
        });
    }
}