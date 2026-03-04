<?php

namespace Database\Factories\Domain\CRM\Models;

use App\Domain\CRM\Enums\LeadSource;
use App\Domain\CRM\Enums\LeadStatus;
use App\Domain\CRM\Models\Lead;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\CRM\Models\Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'company_id' => Company::factory(),
            'assigned_to' => $this->faker->boolean(70) ? User::factory() : null,
            'created_by' => User::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'company_name' => $this->faker->company(),
            'job_title' => $this->faker->jobTitle(),
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country' => 'BD',
            'postal_code' => $this->faker->postcode(),
            'status' => $this->faker->randomElement(LeadStatus::cases()),
            'source' => $this->faker->randomElement(LeadSource::cases()),
            'score' => $this->faker->numberBetween(0, 100),
            'estimated_value' => $this->faker->randomFloat(2, 1000, 100000),
            'currency' => 'BDT',
            'expected_close_date' => $this->faker->dateTimeBetween('now', '+6 months'),
            'last_contacted_at' => $this->faker->boolean(60) ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            'qualified_at' => $this->faker->boolean(30) ? $this->faker->dateTimeBetween('-15 days', 'now') : null,
            'converted_at' => null,
            'converted_to_customer_id' => null,
            'custom_fields' => $this->faker->boolean(30) ? [
                'industry' => $this->faker->randomElement(['Technology', 'Healthcare', 'Finance', 'Manufacturing']),
                'company_size' => $this->faker->randomElement(['1-10', '11-50', '51-200', '200+']),
            ] : null,
            'notes' => $this->faker->boolean(40) ? $this->faker->paragraph() : null,
        ];
    }

    public function newLead(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatus::NEW,
            'score' => $this->faker->numberBetween(0, 30),
            'last_contacted_at' => null,
            'qualified_at' => null,
        ]);
    }

    public function contacted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatus::CONTACTED,
            'score' => $this->faker->numberBetween(20, 60),
            'last_contacted_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function qualified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatus::QUALIFIED,
            'score' => $this->faker->numberBetween(60, 90),
            'last_contacted_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'qualified_at' => $this->faker->dateTimeBetween('-3 days', 'now'),
        ]);
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatus::CONVERTED,
            'score' => $this->faker->numberBetween(80, 100),
            'last_contacted_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'qualified_at' => $this->faker->dateTimeBetween('-10 days', '-5 days'),
            'converted_at' => $this->faker->dateTimeBetween('-3 days', 'now'),
        ]);
    }

    public function highScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $this->faker->numberBetween(70, 100),
        ]);
    }

    public function withEstimatedValue(float $min = 5000, float $max = 50000): static
    {
        return $this->state(fn (array $attributes) => [
            'estimated_value' => $this->faker->randomFloat(2, $min, $max),
        ]);
    }
}