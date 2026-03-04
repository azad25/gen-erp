<?php

namespace Database\Factories\Domain\CMS;

use App\Domain\CMS\Models\PublicOrderItem;
use App\Domain\CMS\Models\PublicOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\CMS\Models\PublicOrderItem>
 */
class PublicOrderItemFactory extends Factory
{
    protected $model = PublicOrderItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 5);
        $unitPrice = $this->faker->randomFloat(2, 10, 200);
        $subtotal = $quantity * $unitPrice;
        $taxAmount = $subtotal * 0.08; // 8% tax
        $total = $subtotal + $taxAmount;
        
        return [
            'order_id' => PublicOrder::factory(),
            'product_id' => $this->faker->numberBetween(1, 100),
            'product_variant_id' => $this->faker->optional(0.3)->numberBetween(1, 50),
            'product_name' => $this->faker->words(3, true),
            'product_sku' => 'SKU-' . $this->faker->bothify('??##??'),
            'variant_name' => $this->faker->optional(0.3)->words(2, true),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ];
    }

    public function forOrder(int $orderId): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $orderId,
        ]);
    }

    public function forProduct(int $productId): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $productId,
        ]);
    }

    public function withVariant(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_variant_id' => $this->faker->numberBetween(1, 50),
            'variant_name' => $this->faker->words(2, true),
        ]);
    }

    public function withoutVariant(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_variant_id' => null,
            'variant_name' => null,
        ]);
    }
}