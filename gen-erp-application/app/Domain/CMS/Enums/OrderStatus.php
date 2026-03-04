<?php

namespace App\Domain\CMS\Enums;

/**
 * Order status enumeration for CMS e-commerce.
 */
enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Get the color associated with the status.
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::PROCESSING => 'blue',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
        };
    }

    /**
     * Get the icon associated with the status.
     */
    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'clock',
            self::PROCESSING => 'cog',
            self::COMPLETED => 'check-circle',
            self::CANCELLED => 'x-circle',
        };
    }

    /**
     * Check if the status allows cancellation.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PENDING, self::PROCESSING]);
    }

    /**
     * Check if the status is final (cannot be changed).
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }

    /**
     * Get all statuses that can transition to this status.
     */
    public function getAllowedPreviousStatuses(): array
    {
        return match($this) {
            self::PENDING => [],
            self::PROCESSING => [self::PENDING],
            self::COMPLETED => [self::PENDING, self::PROCESSING],
            self::CANCELLED => [self::PENDING, self::PROCESSING],
        };
    }

    /**
     * Get all statuses that this status can transition to.
     */
    public function getAllowedNextStatuses(): array
    {
        return match($this) {
            self::PENDING => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::COMPLETED, self::CANCELLED],
            self::COMPLETED => [],
            self::CANCELLED => [],
        };
    }

    /**
     * Check if transition to another status is allowed.
     */
    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return in_array($newStatus, $this->getAllowedNextStatuses());
    }
}