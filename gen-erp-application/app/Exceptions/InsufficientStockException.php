<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a stock-out operation would make available quantity negative.
 */
class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly int $productId,
        public readonly float $requested,
        public readonly float $available,
        public readonly int $warehouseId,
    ) {
        parent::__construct(
            __('Insufficient stock: requested :requested but only :available available for product #:product in warehouse #:warehouse.', [
                'requested' => $requested,
                'available' => $available,
                'product' => $productId,
                'warehouse' => $warehouseId,
            ])
        );
    }
}
