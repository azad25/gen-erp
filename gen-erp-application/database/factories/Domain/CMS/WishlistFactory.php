<?php

namespace Database\Factories\Domain\CMS;

use App\Domain\CMS\Models\Wishlist;
use App\Domain\CMS\Models\CustomerAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\CMS\Models\Wishlist>
 */
class WishlistFactory extends Factory
{
    protected $model = Wishlist::class;

    public function definition(): array
    {
        return [
            'customer_id' => CustomerAccount::factory(),
            'product_id' => $this->faker->numberBetween(1, 100), // Mock product ID
            'product_variant_id' => $this->faker->optional(0.3)->numberBetween(1, 50), // 30% have variants
        ];
    }

    public function withVariant(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_variant_id' => $this->faker->numberBetween(1, 50),
        ]);
    }

    public function withoutVariant(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_variant_id' => null,
        ]);
    }

    public function forCustomer(int $customerId): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customerId,
        ]);
    }

    public function forProduct(int $productId): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $productId,
        ]);
    }
}