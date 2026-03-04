<?php

namespace Database\Factories\Domain\Project\Models;

use App\Domain\Project\Models\Board;
use App\Domain\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class BoardFactory extends Factory
{
    protected $model = Board::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->words(2, true) . ' Board',
            'description' => $this->faker->optional()->sentence(),
            'type' => $this->faker->randomElement([
                Board::TYPE_KANBAN,
                Board::TYPE_SCRUM
            ]),
            'is_default' => false,
            'settings' => [],
        ];
    }

    public function default(): static
    {
        return $this->state([
            'name' => 'Main Board',
            'is_default' => true,
        ]);
    }

    public function kanban(): static
    {
        return $this->state([
            'type' => Board::TYPE_KANBAN,
        ]);
    }

    public function scrum(): static
    {
        return $this->state([
            'type' => Board::TYPE_SCRUM,
        ]);
    }
}