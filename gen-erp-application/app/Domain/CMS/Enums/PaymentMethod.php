<?php

namespace App\Domain\CMS\Enums;

/**
 * Payment method enumeration for CMS e-commerce.
 */
enum PaymentMethod: string
{
    case COD = 'cod';
    case BANK_TRANSFER = 'bank_transfer';
    case ONLINE = 'online';

    /**
     * Get the human-readable label for the payment method.
     */
    public function label(): string
    {
        return match($this) {
            self::COD => 'Cash on Delivery',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::ONLINE => 'Online Payment',
        };
    }

    /**
     * Get the description for the payment method.
     */
    public function description(): string
    {
        return match($this) {
            self::COD => 'Pay when you receive your order',
            self::BANK_TRANSFER => 'Transfer payment to our bank account',
            self::ONLINE => 'Pay securely online with card or mobile banking',
        };
    }

    /**
     * Get the icon associated with the payment method.
     */
    public function icon(): string
    {
        return match($this) {
            self::COD => 'banknotes',
            self::BANK_TRANSFER => 'building-library',
            self::ONLINE => 'credit-card',
        };
    }

    /**
     * Check if the payment method requires immediate payment.
     */
    public function requiresImmediatePayment(): bool
    {
        return $this === self::ONLINE;
    }

    /**
     * Check if the payment method is available for the given amount.
     */
    public function isAvailableForAmount(float $amount): bool
    {
        return match($this) {
            self::COD => $amount <= 50000, // COD limit
            self::BANK_TRANSFER => true,
            self::ONLINE => true,
        };
    }

    /**
     * Get additional fees for this payment method.
     */
    public function getAdditionalFees(float $amount): float
    {
        return match($this) {
            self::COD => $amount > 1000 ? 50 : 0, // COD fee for orders above 1000
            self::BANK_TRANSFER => 0,
            self::ONLINE => $amount * 0.025, // 2.5% processing fee
        };
    }

    /**
     * Get processing time for this payment method.
     */
    public function getProcessingTime(): string
    {
        return match($this) {
            self::COD => 'On delivery',
            self::BANK_TRANSFER => '1-2 business days',
            self::ONLINE => 'Instant',
        };
    }

    /**
     * Check if this payment method is enabled by default.
     */
    public function isEnabledByDefault(): bool
    {
        return match($this) {
            self::COD => true,
            self::BANK_TRANSFER => true,
            self::ONLINE => false, // Requires gateway setup
        };
    }
}