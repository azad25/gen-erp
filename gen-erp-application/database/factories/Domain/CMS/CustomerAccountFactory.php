<?php

namespace Database\Factories\Domain\CMS;

use App\Domain\CMS\Models\CustomerAccount;
use App\Domain\CMS\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\CMS\Models\CustomerAccount>
 */
class CustomerAccountFactory extends Factory
{
    protected $model = CustomerAccount::class;

    public function definition(): array
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();
        
        return [
            'site_id' => Site::factory(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $this->faker->phoneNumber(),
            'is_guest' => false,
            'email_verified_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => null,
            'is_guest' => true,
            'email_verified_at' => null,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withPhone(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => $this->faker->phoneNumber(),
        ]);
    }

    public function withoutPhone(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => null,
        ]);
    }
}