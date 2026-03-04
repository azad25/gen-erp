<?php

namespace Database\Factories\Domain\Document\Models;

use App\Domain\Document\Models\FormSubmission;
use App\Domain\Document\Models\Form;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Document\Models\FormSubmission>
 */
class FormSubmissionFactory extends Factory
{
    protected $model = FormSubmission::class;

    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'submitted_by' => $this->faker->boolean(70) ? User::factory() : null,
            'submission_data' => [
                'name' => $this->faker->name(),
                'email' => $this->faker->email(),
                'message' => $this->faker->paragraph(),
            ],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'status' => $this->faker->randomElement(['pending', 'processed', 'archived']),
            'submitted_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processed',
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }

    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'submitted_by' => null,
        ]);
    }

    public function authenticated(): static
    {
        return $this->state(fn (array $attributes) => [
            'submitted_by' => User::factory(),
        ]);
    }
}