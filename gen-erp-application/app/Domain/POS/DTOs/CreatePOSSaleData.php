<?php

namespace App\Domain\POS\DTOs;

readonly class CreatePOSSaleData
{
    /**
     * @param array<POSSaleItemData> $items
     */
    public function __construct(
        public int $sessionId,
        public ?int $customerId,
        public array $items,
        public int $amountTendered,
        public ?int $paymentMethodId = null,
    ) {}

    public function calculateSubtotal(): int
    {
        return array_reduce(
            $this->items,
            fn($carry, POSSaleItemData $item) => $carry + (int) ($item->quantity * $item->unitPrice),
            0
        );
    }

    public function calculateTotalDiscount(): int
    {
        return array_reduce(
            $this->items,
            fn($carry, POSSaleItemData $item) => $carry + $item->discountAmount,
            0
        );
    }

    public function calculateTotalTax(): int
    {
        return array_reduce(
            $this->items,
            fn($carry, POSSaleItemData $item) => $carry + $item->taxAmount,
            0
        );
    }

    public function calculateTotal(): int
    {
        return $this->calculateSubtotal() - $this->calculateTotalDiscount() + $this->calculateTotalTax();
    }
}
