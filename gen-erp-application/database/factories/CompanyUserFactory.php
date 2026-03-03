<?php

namespace Database\Factories;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\CompanyUser;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyUser>
 */
class CompanyUserFactory extends Factory
{
    protected $model = CompanyUser::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'role' => 'employee',
            'is_owner' => false,
            'joined_at' => now(),
            'is_active' => true,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (): array => [
            'role' => 'owner',
            'is_owner' => true,
        ]);
    }
}
