<?php

namespace Database\Factories\Domain\Logistics;

use App\Domain\Auth\Models\Company;
use App\Domain\Logistics\Enums\CarrierType;
use App\Domain\Logistics\Models\Carrier;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarrierFactory extends Factory
{
    protected $model = Carrier::class;

    public function definition(): array
    {
        $carrierType = $this->faker->randomElement(CarrierType::cases());
        
        return [
            'company_id' => Company::factory(),
            'name' => $carrierType->label(),
            'code' => $carrierType,
            'api_endpoint' => $this->faker->url,
            'api_key' => $this->faker->uuid,
            'api_secret' => $this->faker->uuid,
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'supports_cod' => $carrierType->supportsCOD(),
            'supports_tracking' => $carrierType->supportsTracking(),
            'base_rate' => $this->faker->randomFloat(2, 30, 100),
            'per_kg_rate' => $this->faker->randomFloat(2, 5, 20),
            'cod_charge_percentage' => $this->faker->randomFloat(2, 1, 3),
            'settings' => [
                'max_weight' => $this->faker->numberBetween(10, 50),
                'service_areas' => $this->faker->randomElements(['dhaka', 'chittagong', 'sylhet', 'rajshahi'], 2),
                'pickup_time' => $this->faker->time('H:i'),
            ],
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function pathao(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Pathao',
            'code' => CarrierType::PATHAO,
            'api_endpoint' => 'https://api.pathao.com/v1',
            'supports_cod' => true,
            'supports_tracking' => true,
        ]);
    }

    public function paperfly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'PaperFly',
            'code' => CarrierType::PAPERFLY,
            'api_endpoint' => 'https://api.paperfly.com.bd/v1',
            'supports_cod' => true,
            'supports_tracking' => true,
        ]);
    }

    public function steadfast(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'SteadFast',
            'code' => CarrierType::STEADFAST,
            'api_endpoint' => 'https://portal.steadfast.com.bd/api/v1',
            'supports_cod' => true,
            'supports_tracking' => true,
        ]);
    }
}