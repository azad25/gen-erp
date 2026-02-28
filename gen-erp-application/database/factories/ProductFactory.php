<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'company_id' => Company::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'sku' => strtoupper(fake()->unique()->lexify('???-######')),
            'product_type' => ProductType::PRODUCT->value,
            'unit' => 'pcs',
            'cost_price' => fake()->numberBetween(1000, 10000) * 100,
            'selling_price' => fake()->numberBetween(10000, 50000) * 100,
            'min_selling_price' => 0,
            'track_inventory' => true,
            'is_active' => true,
        ];
    }

    public function service(): static
    {
        return $this->state(fn (): array => [
            'product_type' => ProductType::SERVICE->value,
            'track_inventory' => false,
        ]);
    }

    public function digital(): static
    {
        return $this->state(fn (): array => [
            'product_type' => ProductType::DIGITAL->value,
            'track_inventory' => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
