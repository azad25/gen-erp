<?php

namespace App\Enums;

enum StockMovementType: string
{
    case PURCHASE_RECEIPT = 'purchase_receipt';
    case SALE = 'sale';
    case SALE_RETURN = 'sale_return';
    case PURCHASE_RETURN = 'purchase_return';
    case ADJUSTMENT_IN = 'adjustment_in';
    case ADJUSTMENT_OUT = 'adjustment_out';
    case TRANSFER_IN = 'transfer_in';
    case TRANSFER_OUT = 'transfer_out';
    case OPENING_STOCK = 'opening_stock';
    case PRODUCTION_IN = 'production_in';
    case PRODUCTION_OUT = 'production_out';

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE_RECEIPT => __('Purchase Receipt'),
            self::SALE => __('Sale'),
            self::SALE_RETURN => __('Sale Return'),
            self::PURCHASE_RETURN => __('Purchase Return'),
            self::ADJUSTMENT_IN => __('Adjustment In'),
            self::ADJUSTMENT_OUT => __('Adjustment Out'),
            self::TRANSFER_IN => __('Transfer In'),
            self::TRANSFER_OUT => __('Transfer Out'),
            self::OPENING_STOCK => __('Opening Stock'),
            self::PRODUCTION_IN => __('Production In'),
            self::PRODUCTION_OUT => __('Production Out'),
        };
    }

    public function isInbound(): bool
    {
        return in_array($this, [
            self::PURCHASE_RECEIPT,
            self::SALE_RETURN,
            self::ADJUSTMENT_IN,
            self::TRANSFER_IN,
            self::OPENING_STOCK,
            self::PRODUCTION_IN,
        ]);
    }

    public function isOutbound(): bool
    {
        return ! $this->isInbound();
    }

    public function color(): string
    {
        return $this->isInbound() ? 'success' : 'danger';
    }
}
