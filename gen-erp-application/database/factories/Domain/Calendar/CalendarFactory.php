<?php

namespace Database\Factories\Domain\Calendar;

use App\Domain\Calendar\Models\Calendar;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CalendarFactory extends Factory
{
    protected $model = Calendar::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'team_id' => null,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['personal', 'team', 'company', 'resource']),
            'color' => $this->faker->randomElement(['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899']),
            'is_default' => false,
            'is_public' => $this->faker->boolean(30),
            'timezone' => 'UTC',
        ];
    }

    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'personal',
            'is_default' => true,
        ]);
    }

    public function team(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'team',
            'user_id' => null,
        ]);
    }

    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'company',
            'user_id' => null,
            'is_public' => true,
        ]);
    }

    public function resource(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'resource',
            'user_id' => null,
        ]);
    }
}
