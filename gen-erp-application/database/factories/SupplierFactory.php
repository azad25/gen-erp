<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->company(),
            'contact_person' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => '01'.fake()->randomElement([3, 4, 5, 6, 7, 8, 9]).fake()->numerify('########'),
            'city' => 'Dhaka',
            'tds_rate' => 0,
            'vds_rate' => 0,
            'credit_days' => 0,
            'opening_balance' => 0,
            'is_active' => true,
        ];
    }

    public function withTds(float $rate): static
    {
        return $this->state(fn (): array => ['tds_rate' => $rate]);
    }

    public function withVds(float $rate): static
    {
        return $this->state(fn (): array => ['vds_rate' => $rate]);
    }
}
