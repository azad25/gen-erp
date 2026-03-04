<?php

namespace Database\Factories\Domain\Logistics;

use App\Domain\Logistics\Enums\ShipmentStatus;
use App\Domain\Logistics\Models\Shipment;
use App\Domain\Logistics\Models\TrackingEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrackingEventFactory extends Factory
{
    protected $model = TrackingEvent::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(ShipmentStatus::cases());
        
        return [
            'shipment_id' => Shipment::factory(),
            'status' => $status,
            'location' => $this->faker->city() . ', Bangladesh',
            'description' => $status->label(),
            'event_time' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'carrier_status' => $this->faker->optional()->word(),
            'carrier_data' => $this->faker->optional()->randomElements([
                'carrier_event_id' => $this->faker->uuid(),
                'carrier_location' => $this->faker->city(),
                'carrier_timestamp' => $this->faker->iso8601(),
            ]),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShipmentStatus::PENDING,
            'description' => 'Shipment created and pending pickup',
        ]);
    }

    public function pickedUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShipmentStatus::PICKED_UP,
            'description' => 'Package picked up by carrier',
        ]);
    }

    public function inTransit(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShipmentStatus::IN_TRANSIT,
            'description' => 'Package in transit to destination',
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShipmentStatus::DELIVERED,
            'description' => 'Package delivered successfully',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShipmentStatus::FAILED,
            'description' => 'Delivery failed - recipient not available',
        ]);
    }
}