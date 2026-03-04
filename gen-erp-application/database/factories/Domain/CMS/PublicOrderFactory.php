<?php

namespace Database\Factories\Domain\CMS;

use App\Domain\CMS\Models\PublicOrder;
use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\CustomerAccount;
use App\Domain\CMS\Enums\OrderStatus;
use App\Domain\CMS\Enums\PaymentStatus;
use App\Domain\CMS\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\CMS\Models\PublicOrder>
 */
class PublicOrderFactory extends Factory
{
    protected $model = PublicOrder::class;

    public function definition(): array
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();
        $subtotal = $this->faker->randomFloat(2, 10, 500);
        $shippingCost = $this->faker->randomFloat(2, 0, 25);
        $taxAmount = $subtotal * 0.08; // 8% tax
        $discountAmount = $this->faker->optional(0.3)->randomFloat(2, 0, $subtotal * 0.2) ?? 0;
        $totalAmount = $subtotal + $shippingCost + $taxAmount - $discountAmount;
        
        return [
            'site_id' => Site::factory(),
            'customer_id' => CustomerAccount::factory(),
            'order_number' => 'ORD-' . strtoupper($this->faker->bothify('??##??##')),
            'customer_email' => $this->faker->safeEmail(),
            'customer_first_name' => $firstName,
            'customer_last_name' => $lastName,
            'customer_phone' => $this->faker->optional()->phoneNumber(),
            'billing_address_line_1' => $this->faker->streetAddress(),
            'billing_address_line_2' => $this->faker->optional()->secondaryAddress(),
            'billing_city' => $this->faker->city(),
            'billing_state' => $this->faker->state(),
            'billing_postal_code' => $this->faker->postcode(),
            'billing_country' => $this->faker->countryCode(),
            'shipping_address_line_1' => $this->faker->streetAddress(),
            'shipping_address_line_2' => $this->faker->optional()->secondaryAddress(),
            'shipping_city' => $this->faker->city(),
            'shipping_state' => $this->faker->state(),
            'shipping_postal_code' => $this->faker->postcode(),
            'shipping_country' => $this->faker->countryCode(),
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'status' => $this->faker->randomElement(OrderStatus::cases()),
            'payment_status' => $this->faker->randomElement(PaymentStatus::cases()),
            'payment_method' => $this->faker->randomElement(PaymentMethod::cases()),
            'customer_notes' => $this->faker->optional()->sentence(),
            'admin_notes' => $this->faker->optional()->sentence(),
            'tracking_number' => $this->faker->optional()->bothify('1Z???###??######'),
            'placed_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'completed_at' => $this->faker->optional(0.6)->dateTimeBetween('-2 months', 'now'),
            'cancelled_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::PROCESSING,
            'payment_status' => PaymentStatus::PAID,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => PaymentStatus::PAID,
            'completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'cancelled_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CANCELLED,
            'payment_status' => PaymentStatus::FAILED,
            'completed_at' => null,
            'cancelled_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function withTracking(): static
    {
        return $this->state(fn (array $attributes) => [
            'tracking_number' => $this->faker->bothify('1Z???###??######'),
        ]);
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => null,
        ]);
    }
}