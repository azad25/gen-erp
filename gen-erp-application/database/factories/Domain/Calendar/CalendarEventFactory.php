<?php

namespace Database\Factories\Domain\Calendar;

use App\Domain\Calendar\Models\Calendar;
use App\Domain\Calendar\Models\CalendarEvent;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        $startAt = Carbon::now()->addDays($this->faker->numberBetween(1, 30));
        $endAt = (clone $startAt)->addHours($this->faker->numberBetween(1, 4));

        return [
            'company_id' => Company::factory(),
            'calendar_id' => Calendar::factory(),
            'user_id' => User::factory(),
            'eventable_type' => null,
            'eventable_id' => null,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'location' => $this->faker->optional()->address(),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'all_day' => false,
            'type' => $this->faker->randomElement(['meeting', 'call', 'task', 'deadline', 'leave', 'milestone', 'personal', 'company']),
            'status' => $this->faker->randomElement(['scheduled', 'in_progress', 'completed', 'cancelled']),
            'color' => $this->faker->randomElement(['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#6B7280', '#6366F1']),
            'is_recurring' => false,
            'recurrence_rule' => null,
            'reminder_minutes' => $this->faker->optional()->randomElement([15, 30, 60, 120]),
            'attendees' => null,
            'metadata' => null,
        ];
    }

    public function meeting(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'meeting',
            'color' => '#3B82F6',
        ]);
    }

    public function call(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'call',
            'color' => '#10B981',
        ]);
    }

    public function task(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'task',
            'color' => '#F59E0B',
        ]);
    }

    public function deadline(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deadline',
            'color' => '#EF4444',
        ]);
    }

    public function leave(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'leave',
            'color' => '#8B5CF6',
            'all_day' => true,
        ]);
    }

    public function milestone(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'milestone',
            'color' => '#EC4899',
        ]);
    }

    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'personal',
            'color' => '#6B7280',
        ]);
    }

    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'company',
            'color' => '#6366F1',
        ]);
    }

    public function allDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'all_day' => true,
            'end_at' => $attributes['start_at'],
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
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

    public function withAttendees(): static
    {
        return $this->state(fn (array $attributes) => [
            'attendees' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'accepted'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'status' => 'pending'],
            ],
        ]);
    }
}
