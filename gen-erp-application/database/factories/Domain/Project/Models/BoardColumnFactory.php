<?php

namespace Database\Factories\Domain\Project\Models;

use App\Domain\Project\Models\BoardColumn;
use App\Domain\Project\Models\Board;
use Illuminate\Database\Eloquent\Factories\Factory;

class BoardColumnFactory extends Factory
{
    protected $model = BoardColumn::class;

    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'name' => $this->faker->randomElement([
                'To Do', 'In Progress', 'In Review', 'Testing', 'Done'
            ]),
            'description' => $this->faker->optional()->sentence(),
            'color' => $this->faker->hexColor(),
            'position' => $this->faker->numberBetween(1, 10),
            'wip_limit' => $this->faker->optional()->numberBetween(3, 10),
            'is_done_column' => false,
            'settings' => [],
        ];
    }

    public function todo(): static
    {
        return $this->state([
            'name' => 'To Do',
            'color' => '#6B7280',
            'position' => 1,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state([
            'name' => 'In Progress',
            'color' => '#3B82F6',
            'position' => 2,
        ]);
    }

    public function done(): static
    {
        return $this->state([
            'name' => 'Done',
            'color' => '#10B981',
            'position' => 99,
            'is_done_column' => true,
        ]);
    }

    public function withWipLimit(int $limit): static
    {
        return $this->state([
            'wip_limit' => $limit,
        ]);
    }
}