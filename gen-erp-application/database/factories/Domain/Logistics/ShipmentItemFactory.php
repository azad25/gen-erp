<?php

namespace Database\Factories\Domain\Logistics;

use App\Domain\Logistics\Models\Shipment;
use App\Domain\Logistics\Models\ShipmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentItemFactory extends Factory
{
    protected $model = ShipmentItem::class;

    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'product_variant_id' => null,
            'invoice_item_id' => null,
            'product_name' => $this->faker->words(3, true),
            'sku' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->randomFloat(2, 10, 500),
            'total_price' => function (array $attributes) {
                return $attributes['quantity'] * $attributes['unit_price'];
            },
        ];
    }

    public function withProductVariant(int $productVariantId): static
    {
        return $this->state(fn (array $attributes) => [
            'product_variant_id' => $productVariantId,
        ]);
    }

    public function withInvoiceItem(int $invoiceItemId): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_item_id' => $invoiceItemId,
        ]);
    }
}