<?php

namespace Database\Factories\Domain\POS;

use App\Domain\Auth\Models\Company;
use App\Domain\POS\Models\POSSale;
use App\Domain\POS\Models\POSSaleItem;
use App\Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class POSSaleItemFactory extends Factory
{
    protected $model = POSSaleItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->numberBetween(10000, 500000);
        $discountAmount = $this->faker->numberBetween(0, $unitPrice * $quantity * 0.1);
        $taxAmount = (int) (($unitPrice * $quantity - $discountAmount) * 0.15);
        $lineTotal = ($unitPrice * $quantity) - $discountAmount + $taxAmount;

        return [
            'pos_sale_id' => POSSale::factory(),
            'company_id' => Company::factory(),
            'product_id' => Product::factory(),
            'description' => $this->faker->sentence(3),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'line_total' => $lineTotal,
        ];
    }
}
