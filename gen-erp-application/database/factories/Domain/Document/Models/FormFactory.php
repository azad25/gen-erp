<?php

namespace Database\Factories\Domain\Document\Models;

use App\Domain\Document\Models\Form;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Document\Models\Form>
 */
class FormFactory extends Factory
{
    protected $model = Form::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'slug' => $this->faker->unique()->slug(),
            'is_public' => $this->faker->boolean(),
            'is_active' => true,
            'settings' => [
                'success_message' => 'Thank you for your submission!',
                'redirect_url' => null,
                'allow_multiple_submissions' => true,
            ],
            'created_by' => User::factory(),
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}