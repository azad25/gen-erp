<?php

namespace Database\Factories\Domain\Logistics;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Logistics\Enums\ReturnReason;
use App\Domain\Logistics\Models\Carrier;
use App\Domain\Logistics\Models\Shipment;
use App\Domain\Logistics\Models\ShipmentReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentReturnFactory extends Factory
{
    protected $model = ShipmentReturn::class;

    public function definition(): array
    {
        $reason = $this->faker->randomElement(ReturnReason::cases());
        
        return [
            'company_id' => Company::factory(),
            'shipment_id' => Shipment::factory(),
            'reason' => $reason,
            'reason_details' => $this->faker->sentence(),
            'status' => 'requested',
            'return_tracking_number' => null,
            'return_carrier_id' => null,
            'refund_amount' => null,
            'refund_method' => null,
            'refunded_at' => null,
            'images' => $reason->requiresImages() ? [$this->faker->imageUrl()] : null,
            'requested_by' => User::factory(),
            'approved_by' => null,
            'requested_at' => $this->faker->dateTimeBetween('-3 days', 'now'),
            'approved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            $requestedAt = $attributes['requested_at'] ?? $this->faker->dateTimeBetween('-3 days', 'now');
            
            return [
                'status' => 'approved',
                'approved_by' => User::factory(),
                'approved_at' => $this->faker->dateTimeBetween($requestedAt, 'now'),
                'return_carrier_id' => Carrier::factory(),
                'return_tracking_number' => 'RET-' . strtoupper($this->faker->bothify('??######')),
            ];
        });
    }

    public function rejected(): static
    {
        return $this->state(function (array $attributes) {
            $requestedAt = $attributes['requested_at'] ?? $this->faker->dateTimeBetween('-3 days', 'now');
            
            return [
                'status' => 'rejected',
                'approved_by' => User::factory(),
                'approved_at' => $this->faker->dateTimeBetween($requestedAt, 'now'),
            ];
        });
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $requestedAt = $this->faker->dateTimeBetween('-5 days', '-2 days');
            $approvedAt = $this->faker->dateTimeBetween($requestedAt, '-1 day');
            
            return [
                'requested_at' => $requestedAt,
                'status' => 'refunded',
                'approved_by' => User::factory(),
                'approved_at' => $approvedAt,
                'return_carrier_id' => Carrier::factory(),
                'return_tracking_number' => 'RET-' . strtoupper($this->faker->bothify('??######')),
                'refund_amount' => $this->faker->randomFloat(2, 100, 2000),
                'refund_method' => $this->faker->randomElement(['bank_transfer', 'mobile_banking', 'cash']),
                'refunded_at' => $this->faker->dateTimeBetween($approvedAt, 'now'),
            ];
        });
    }

    public function withImages(): static
    {
        return $this->state(fn (array $attributes) => [
            'images' => [
                $this->faker->imageUrl(640, 480, 'products'),
                $this->faker->imageUrl(640, 480, 'products'),
            ],
        ]);
    }

    public function defective(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => ReturnReason::DAMAGED,
            'reason_details' => 'Product arrived damaged/defective',
            'images' => [
                $this->faker->imageUrl(640, 480, 'products'),
                $this->faker->imageUrl(640, 480, 'products'),
            ],
        ]);
    }

    public function wrongItem(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => ReturnReason::WRONG_ITEM,
            'reason_details' => 'Received wrong item/size/color',
        ]);
    }

    public function notSatisfied(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => ReturnReason::NOT_SATISFIED,
            'reason_details' => 'Product did not meet expectations',
        ]);
    }
}