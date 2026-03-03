<?php

namespace Database\Factories\Domain\Purchase\Models;

use App\Domain\Auth\Models\Company;
use App\Domain\Purchase\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'supplier_code' => 'SUPP-' . $this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->company(),
            'contact_person' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address_line1' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'vat_bin' => null,
            'tds_rate' => 0.0,
            'vds_rate' => 0.0,
            'is_active' => true,
        ];
    }

    public function withTds(float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'tds_rate' => $rate,
        ]);
    }

    public function withVds(float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'vds_rate' => $rate,
        ]);
    }

    public function withVat(): static
    {
        return $this->state(fn (array $attributes) => [
            'vat_bin' => $this->faker->numerify('###########'),
        ]);
    }
}