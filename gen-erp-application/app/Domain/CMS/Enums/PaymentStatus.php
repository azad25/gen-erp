<?php

namespace App\Domain\CMS\Enums;

/**
 * Payment status enumeration for CMS e-commerce.
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';

    /**
     * Get the human-readable label for the payment status.
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::FAILED => 'Failed',
        };
    }

    /**
     * Get the color associated with the payment status.
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::PAID => 'green',
            self::FAILED => 'red',
        };
    }

    /**
     * Get the icon associated with the payment status.
     */
    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'clock',
            self::PAID => 'check-circle',
            self::FAILED => 'x-circle',
        };
    }

    /**
     * Check if the payment status is successful.
     */
    public function isSuccessful(): bool
    {
        return $this === self::PAID;
    }

    /**
     * Check if the payment status is final (cannot be changed).
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::PAID, self::FAILED]);
    }

    /**
     * Check if payment can be retried.
     */
    public function canRetry(): bool
    {
        return in_array($this, [self::PENDING, self::FAILED]);
    }
}