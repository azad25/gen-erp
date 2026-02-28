<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\GoodsReceipt;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceipt>
 */
class GoodsReceiptFactory extends Factory
{
    protected $model = GoodsReceipt::class;

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
