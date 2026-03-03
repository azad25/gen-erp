<?php

namespace Database\Factories\Domain\Inventory\Models;

use App\Domain\Auth\Models\Company;
use App\Domain\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => 'WH-' . $this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->company() . ' Warehouse',
            'address' => $this->faker->address(),
            'is_active' => true,
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}