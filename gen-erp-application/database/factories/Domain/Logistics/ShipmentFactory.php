<?php

namespace Database\Factories\Domain\Logistics;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Logistics\Enums\DeliveryType;
use App\Domain\Logistics\Enums\ShipmentStatus;
use App\Domain\Logistics\Models\Carrier;
use App\Domain\Logistics\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        $deliveryType = $this->faker->randomElement(DeliveryType::cases());
        $paymentMethod = $this->faker->randomElement(['prepaid', 'cod']);
        $codAmount = $paymentMethod === 'cod' ? $this->faker->randomFloat(2, 500, 5000) : 0;
        $shippingCost = $this->faker->randomFloat(2, 50, 200);
        $codCharge = $paymentMethod === 'cod' ? ($codAmount * 0.015) : 0; // 1.5% COD charge
        
        return [
            'uuid' => (string) Str::uuid(),
            'company_id' => Company::factory(),
            'carrier_id' => Carrier::factory(),
            'invoice_id' => null,
            'customer_id' => \App\Domain\Customer\Models\Customer::factory(),
            'tracking_number' => 'SHP-' . strtoupper(Str::random(8)),
            'carrier_tracking_number' => $this->faker->optional()->numerify('########'),
            
            // Sender Info
            'sender_name' => $this->faker->company,
            'sender_phone' => $this->faker->phoneNumber,
            'sender_address' => $this->faker->address,
            'sender_city' => $this->faker->randomElement(['Dhaka', 'Chittagong', 'Sylhet', 'Rajshahi']),
            'sender_area' => $this->faker->optional()->city,
            'sender_postcode' => $this->faker->optional()->postcode,
            
            // Recipient Info
            'recipient_name' => $this->faker->name,
            'recipient_phone' => $this->faker->phoneNumber,
            'recipient_email' => $this->faker->optional()->email,
            'recipient_address' => $this->faker->address,
            'recipient_city' => $this->faker->randomElement(['Dhaka', 'Chittagong', 'Sylhet', 'Rajshahi']),
            'recipient_area' => $this->faker->optional()->city,
            'recipient_postcode' => $this->faker->optional()->postcode,
            
            // Shipment Details
            'status' => $this->faker->randomElement(ShipmentStatus::cases()),
            'delivery_type' => $deliveryType,
            'payment_method' => $paymentMethod,
            
            // Pricing
            'cod_amount' => $codAmount,
            'shipping_cost' => $shippingCost,
            'cod_charge' => $codCharge,
            'total_cost' => $shippingCost + $codCharge,
            
            // Weight & Dimensions
            'weight' => $this->faker->randomFloat(2, 0.5, 10),
            'length' => $this->faker->optional()->randomFloat(2, 10, 50),
            'width' => $this->faker->optional()->randomFloat(2, 10, 50),
            'height' => $this->faker->optional()->randomFloat(2, 5, 30),
            
            // Dates
            'pickup_date' => $this->faker->optional()->dateTimeBetween('now', '+2 days'),
            'expected_delivery_date' => $this->faker->dateTimeBetween('+1 day', '+7 days'),
            'actual_delivery_date' => $this->faker->optional(0.3)->dateTimeBetween('-7 days', 'now'),
            
            // Additional Info
            'special_instructions' => $this->faker->optional()->sentence,
            'package_description' => $this->faker->optional()->words(3, true),
            'carrier_response' => $this->faker->optional()->passthrough([
                'carrier_id' => $this->faker->uuid,
                'status' => 'created',
                'message' => 'Shipment created successfully',
            ]),
            
            'created_by' => User::factory(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShipmentStatus::PENDING,
            'actual_delivery_date' => null,
        ]);
    }

    public function inTransit(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShipmentStatus::IN_TRANSIT,
            'pickup_date' => $this->faker->dateTimeBetween('-2 days', 'now'),
            'actual_delivery_date' => null,
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShipmentStatus::DELIVERED,
            'pickup_date' => $this->faker->dateTimeBetween('-5 days', '-2 days'),
            'actual_delivery_date' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    public function cod(): static
    {
        return $this->state(function (array $attributes) {
            $codAmount = $this->faker->randomFloat(2, 500, 5000);
            $codCharge = $codAmount * 0.015; // 1.5% COD charge
            
            return [
                'payment_method' => 'cod',
                'cod_amount' => $codAmount,
                'cod_charge' => $codCharge,
                'total_cost' => $attributes['shipping_cost'] + $codCharge,
            ];
        });
    }

    public function prepaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'prepaid',
            'cod_amount' => 0,
            'cod_charge' => 0,
            'total_cost' => $attributes['shipping_cost'],
        ]);
    }

    public function express(): static
    {
        return $this->state(fn (array $attributes) => [
            'delivery_type' => DeliveryType::EXPRESS,
            'expected_delivery_date' => $this->faker->dateTimeBetween('+1 day', '+2 days'),
        ]);
    }

    public function sameDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'delivery_type' => DeliveryType::SAME_DAY,
            'expected_delivery_date' => now()->addHours(8),
        ]);
    }
}