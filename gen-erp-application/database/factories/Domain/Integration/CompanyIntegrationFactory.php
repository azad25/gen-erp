<?php

namespace Database\Factories\Domain\Integration;

use App\Domain\Auth\Models\Company;
use App\Domain\Integration\Models\CompanyIntegration;
use App\Domain\Integration\Models\Integration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyIntegration>
 */
class CompanyIntegrationFactory extends Factory
{
    protected $model = CompanyIntegration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'integration_id' => Integration::factory(),
            'config' => [
                'api_key' => fake()->uuid(),
                'endpoint' => fake()->url(),
            ],
            'field_maps' => [
                'customer_name' => 'full_name',
                'customer_email' => 'email',
            ],
            'status' => 'active',
            'last_sync_at' => null,
            'last_error' => null,
            'installed_at' => now(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn(): array => [
            'status' => 'active',
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn(): array => [
            'status' => 'paused',
        ]);
    }

    public function error(): static
    {
        return $this->state(fn(): array => [
            'status' => 'error',
            'last_error' => 'Connection timeout',
        ]);
    }

    public function synced(): static
    {
        return $this->state(fn(): array => [
            'last_sync_at' => now()->subHours(2),
        ]);
    }

    public function withError(): static
    {
        return $this->state(fn(): array => [
            'last_error' => fake()->sentence(),
        ]);
    }
}
