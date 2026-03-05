<?php

namespace Database\Factories\Domain\Calendar\Models;

use App\Domain\Calendar\Models\Calendar;
use App\Domain\Calendar\Models\CalendarEvent;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        $startTime = $this->faker->dateTimeBetween('now', '+1 month');
        $endTime = (clone $startTime)->modify('+1 hour');

        return [
            'company_id' => function (array $attributes) {
                return Calendar::find($attributes['calendar_id'])->company_id ?? Company::factory();
            },
            'calendar_id' => Calendar::factory(),
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'location' => $this->faker->address(),
            'start_at' => $startTime,
            'end_at' => $endTime,
            'all_day' => false,
            'type' => 'personal',
            'status' => 'scheduled',
            'color' => $this->faker->hexColor(),
            'is_recurring' => false,
            'recurrence_rule' => null,
        ];
    }

    public function allDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'all_day' => true,
            'start_at' => $this->faker->dateTimeBetween('now', '+1 month')->setTime(0, 0),
            'end_at' => $this->faker->dateTimeBetween('now', '+1 month')->setTime(23, 59, 59),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
            'recurrence_rule' => 'FREQ=WEEKLY;BYDAY=MO,WE,FR',
        ]);
    }
}
