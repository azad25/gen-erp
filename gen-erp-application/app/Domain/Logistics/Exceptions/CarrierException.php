<?php

namespace App\Domain\Logistics\Exceptions;

use Exception;

class CarrierException extends Exception
{
    public static function notFound(string $carrierCode): self
    {
        return new self("Carrier not found: {$carrierCode}");
    }

    public static function notActive(string $carrierName): self
    {
        return new self("Carrier is not active: {$carrierName}");
    }

    public static function apiConnectionFailed(string $carrierName): self
    {
        return new self("Failed to connect to {$carrierName} API");
    }

    public static function invalidCredentials(string $carrierName): self
    {
        return new self("Invalid API credentials for {$carrierName}");
    }

    public static function serviceUnavailable(string $carrierName): self
    {
        return new self("Service unavailable for {$carrierName}");
    }

    public static function unsupportedOperation(string $carrierName, string $operation): self
    {
        return new self("Operation '{$operation}' not supported by {$carrierName}");
    }

    public static function rateCalculationFailed(string $carrierName): self
    {
        return new self("Failed to calculate rates for {$carrierName}");
    }
}