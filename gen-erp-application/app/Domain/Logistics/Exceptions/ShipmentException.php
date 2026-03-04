<?php

namespace App\Domain\Logistics\Exceptions;

use Exception;

class ShipmentException extends Exception
{
    public static function carrierNotConfigured(string $carrierName): self
    {
        return new self("Carrier {$carrierName} is not properly configured");
    }

    public static function cannotCancel(string $status): self
    {
        return new self("Cannot cancel shipment in status: {$status}");
    }

    public static function cannotUpdate(string $status): self
    {
        return new self("Cannot update shipment in status: {$status}");
    }

    public static function trackingNotFound(string $trackingNumber): self
    {
        return new self("Shipment not found with tracking number: {$trackingNumber}");
    }

    public static function invalidWeight(float $weight): self
    {
        return new self("Invalid weight: {$weight}kg. Weight must be greater than 0");
    }

    public static function invalidDimensions(): self
    {
        return new self("Invalid package dimensions provided");
    }

    public static function carrierApiError(string $message): self
    {
        return new self("Carrier API error: {$message}");
    }
}