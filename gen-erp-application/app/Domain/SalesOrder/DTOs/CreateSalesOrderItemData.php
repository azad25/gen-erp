<?php

namespace App\Domain\SalesOrder\DTOs;

readonly class CreateSalesOrderItemData
{
    public function __construct(
        public ?int $productId,
        public string $description,
        public float $quantity,
        public string $unit,
        public int $unitPrice,
        public float $discountPercent,
        public ?int $taxGroupId,
        public float $taxRate,
    ) {}

    /**
     * Create from array.
     */
    public static function fromArray(array $item): self
    {
        return new self(
            productId: $item['product_id'] ?? null,
            description: $item['description'] ?? '',
            quantity: (float) ($item['quantity'] ?? 0),
            unit: $item['unit'] ?? 'pcs',
            unitPrice: (int) ($item['unit_price'] ?? 0),
            discountPercent: (float) ($item['discount_percent'] ?? 0),
            taxGroupId: $item['tax_group_id'] ?? null,
            taxRate: (float) ($item['tax_rate'] ?? 0),
        );
    }

    /**
     * Convert to array for model creation.
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'unit_price' => $this->unitPrice,
            'discount_percent' => $this->discountPercent,
            'tax_group_id' => $this->taxGroupId,
            'tax_rate' => $this->taxRate,
        ];
    }
}