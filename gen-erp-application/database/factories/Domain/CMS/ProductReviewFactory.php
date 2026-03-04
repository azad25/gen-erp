<?php

namespace Database\Factories\Domain\CMS;

use App\Domain\CMS\Models\ProductReview;
use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\CustomerAccount;
use App\Domain\CMS\Models\PublicOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\CMS\Models\ProductReview>
 */
class ProductReviewFactory extends Factory
{
    protected $model = ProductReview::class;

    public function definition(): array
    {
        $rating = $this->faker->numberBetween(1, 5);
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();
        
        return [
            'site_id' => Site::factory(),
            'product_id' => $this->faker->numberBetween(1, 100), // Mock product ID
            'customer_id' => CustomerAccount::factory(),
            'order_id' => null, // Will be set by states if needed
            'rating' => $rating,
            'title' => $this->faker->optional(0.7)->sentence(3),
            'review' => $this->faker->optional(0.8)->paragraph(3),
            'customer_name' => $firstName . ' ' . $lastName,
            'customer_email' => $this->faker->safeEmail(),
            'is_verified_purchase' => $this->faker->boolean(30), // 30% chance
            'is_approved' => $this->faker->boolean(80), // 80% approved
            'helpful_count' => $this->faker->numberBetween(0, 25),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => true,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
        ]);
    }

    public function verifiedPurchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified_purchase' => true,
            'order_id' => PublicOrder::factory(),
        ]);
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => null,
            'is_verified_purchase' => false,
            'order_id' => null,
        ]);
    }

    public function rating(int $rating): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => max(1, min(5, $rating)),
        ]);
    }

    public function helpful(): static
    {
        return $this->state(fn (array $attributes) => [
            'helpful_count' => $this->faker->numberBetween(10, 50),
        ]);
    }

    public function withTitle(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $this->faker->sentence(4),
        ]);
    }

    public function withReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'review' => $this->faker->paragraphs(2, true),
        ]);
    }
}