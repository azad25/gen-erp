<?php

namespace Database\Factories\Domain\POS;

use App\Domain\Auth\Models\Branch;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Models\PaymentMethod;
use App\Domain\POS\Models\POSSale;
use App\Domain\POS\Models\POSSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class POSSaleFactory extends Factory
{
    protected $model = POSSale::class;

    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(100000, 1000000);
        $discountAmount = $this->faker->numberBetween(0, $subtotal * 0.1);
        $taxAmount = (int) (($subtotal - $discountAmount) * 0.15);
        $totalAmount = $subtotal - $discountAmount + $taxAmount;
        $amountTendered = $totalAmount + $this->faker->numberBetween(0, 100000);

        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'pos_session_id' => POSSession::factory(),
            'customer_id' => Customer::factory(),
            'sale_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'amount_tendered' => $amountTendered,
            'change_amount' => $amountTendered - $totalAmount,
            'payment_method_id' => PaymentMethod::factory(),
            'status' => 'completed',
            'created_by' => User::factory(),
        ];
    }

    public function voided(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'voided',
        ]);
    }
}
