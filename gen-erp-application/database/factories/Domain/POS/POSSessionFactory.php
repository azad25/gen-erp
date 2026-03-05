<?php

namespace Database\Factories\Domain\POS;

use App\Domain\Auth\Models\Branch;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\POS\Models\POSSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class POSSessionFactory extends Factory
{
    protected $model = POSSession::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'opened_by' => User::factory(),
            'opening_cash' => $this->faker->numberBetween(500000, 2000000), // 5,000 - 20,000 BDT
            'status' => 'open',
            'opened_at' => now(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'closed_by' => User::factory(),
            'closing_cash' => $this->faker->numberBetween(1000000, 5000000),
            'expected_cash' => $this->faker->numberBetween(1000000, 5000000),
            'cash_difference' => $this->faker->numberBetween(-50000, 50000),
            'closed_at' => now(),
        ]);
    }
}
