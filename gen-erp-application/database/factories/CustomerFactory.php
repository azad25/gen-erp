<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '01'.fake()->randomElement([3, 4, 5, 6, 7, 8, 9]).fake()->numerify('########'),
            'city' => 'Dhaka',
            'credit_limit' => 0,
            'credit_days' => 0,
            'opening_balance' => 0,
            'is_active' => true,
        ];
    }

    public function withCreditLimit(int $limitPaise): static
    {
        return $this->state(fn (): array => ['credit_limit' => $limitPaise]);
    }

    public function withOpeningBalance(int $balancePaise): static
    {
        return $this->state(fn (): array => [
            'opening_balance' => $balancePaise,
            'opening_balance_date' => now()->subMonth()->toDateString(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
