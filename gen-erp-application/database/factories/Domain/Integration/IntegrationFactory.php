<?php

namespace Database\Factories\Domain\Integration;

use App\Domain\Integration\Models\Integration;
use App\Support\Enums\IntegrationCategory;
use App\Support\Enums\IntegrationTier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Integration>
 */
class IntegrationFactory extends Factory
{
    protected $model = Integration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true) . ' Integration';

        return [
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'name' => $name,
            'category' => fake()->randomElement(IntegrationCategory::cases()),
            'description' => fake()->sentence(),
            'logo_path' => null,
            'tier' => fake()->randomElement(IntegrationTier::cases()),
            'min_plan' => fake()->randomElement(['free', 'pro', 'enterprise']),
            'config_schema' => [
                'api_key' => ['type' => 'string', 'required' => true],
                'endpoint' => ['type' => 'string', 'required' => false],
            ],
            'capabilities' => ['sync', 'webhook', 'oauth'],
            'is_active' => true,
            'is_official' => fake()->boolean(70),
            'version' => '1.0.0',
            'author' => fake()->company(),
            'author_url' => fake()->url(),
        ];
    }

    public function ecommerce(): static
    {
        return $this->state(fn(): array => [
            'category' => IntegrationCategory::ECOMMERCE,
        ]);
    }

    public function accounting(): static
    {
        return $this->state(fn(): array => [
            'category' => IntegrationCategory::ACCOUNTING,
        ]);
    }

    public function native(): static
    {
        return $this->state(fn(): array => [
            'tier' => IntegrationTier::NATIVE,
            'is_official' => true,
        ]);
    }

    public function connector(): static
    {
        return $this->state(fn(): array => [
            'tier' => IntegrationTier::CONNECTOR,
        ]);
    }

    public function plugin(): static
    {
        return $this->state(fn(): array => [
            'tier' => IntegrationTier::PLUGIN,
            'is_official' => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(): array => ['is_active' => false]);
    }

    public function official(): static
    {
        return $this->state(fn(): array => ['is_official' => true]);
    }

    public function requiresPro(): static
    {
        return $this->state(fn(): array => ['min_plan' => 'pro']);
    }

    public function requiresEnterprise(): static
    {
        return $this->state(fn(): array => ['min_plan' => 'enterprise']);
    }
}
