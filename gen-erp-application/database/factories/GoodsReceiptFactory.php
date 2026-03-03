<?php

namespace Database\Factories;

use App\Domain\Auth\Models\Company;
use App\Domain\Purchase\Models\GoodsReceipt;
use App\Domain\Purchase\Models\Supplier;
use App\Domain\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceipt>
 */
class GoodsReceiptFactory extends Factory
{
    protected $model = \App\Domain\Purchase\Models\GoodsReceipt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'supplier_id' => Supplier::factory(),
            'warehouse_id' => Warehouse::factory(),
            'receipt_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
        ];
    }
}
