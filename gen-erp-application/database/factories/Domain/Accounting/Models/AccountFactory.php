<?php

namespace Database\Factories\Domain\Accounting\Models;

use App\Domain\Accounting\Models\Account;
use App\Domain\Auth\Models\Company;
use App\Support\Enums\AccountType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $accountType = $this->faker->randomElement(AccountType::cases())->value;
        $code = $this->faker->unique()->numerify('####');
        $name = $this->faker->words(3, true);
        
        return [
            'company_id' => Company::factory(),
            'code' => $code,
            'name' => $name,
            'account_type' => $accountType,
            'sub_type' => $this->getDefaultSubType($accountType),
            'opening_balance' => 0,
            'opening_balance_date' => now()->startOfYear()->toDateString(),
            'is_system' => false,
            'is_active' => true,
        ];
    }
    
    private function getDefaultSubType(string $accountType): string
    {
        return match($accountType) {
            'asset' => 'cash',
            'liability' => 'current_liability',
            'equity' => 'retained_earnings',
            'revenue', 'income' => 'revenue',
            'expense' => 'operating_expense',
            default => 'other',
        };
    }

    public function revenue(): static
    {
        $code = '4' . $this->faker->numerify('###');
        return $this->state(fn (array $attributes) => [
            'account_type' => AccountType::REVENUE->value,
            'code' => $code,
        ]);
    }

    public function expense(): static
    {
        $code = '6' . $this->faker->numerify('###');
        return $this->state(fn (array $attributes) => [
            'account_type' => AccountType::EXPENSE->value,
            'code' => $code,
        ]);
    }

    public function asset(): static
    {
        $code = '1' . $this->faker->numerify('###');
        return $this->state(fn (array $attributes) => [
            'account_type' => AccountType::ASSET->value,
            'code' => $code,
        ]);
    }

    public function liability(): static
    {
        $code = '2' . $this->faker->numerify('###');
        return $this->state(fn (array $attributes) => [
            'account_type' => AccountType::LIABILITY->value,
            'code' => $code,
        ]);
    }

    public function equity(): static
    {
        $code = '3' . $this->faker->numerify('###');
        return $this->state(fn (array $attributes) => [
            'account_type' => AccountType::EQUITY->value,
            'code' => $code,
        ]);
    }
}
