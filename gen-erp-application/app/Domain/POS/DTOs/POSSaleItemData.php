<?php

namespace App\Domain\POS\DTOs;

readonly class POSSaleItemData
{
    public function __construct(
        public ?int $productId,
        public ?int $variantId,
        public string $description,
        public float $quantity,
        public int $unitPrice,
        public int $discountAmount = 0,
        public int $taxAmount = 0,
    ) {}

    public function calculateLineTotal(): int
    {
        return (int) ($this->quantity * $this->unitPrice) - $this->discountAmount + $this->taxAmount;
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'variant_id' => $this->variantId,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'discount_amount' => $this->discountAmount,
            'tax_amount' => $this->taxAmount,
            'line_total' => $this->calculateLineTotal(),
        ];
    }
}
