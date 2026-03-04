<?php

namespace App\Domain\Logistics\Contracts;

use App\Domain\Logistics\DTOs\CarrierRateData;
use App\Domain\Logistics\DTOs\ShipmentData;
use App\Domain\Logistics\DTOs\TrackingData;
use Carbon\Carbon;

interface CarrierServiceInterface
{
    /**
     * Create a shipment with the carrier
     */
    public function createShipment(ShipmentData $data): array;

    /**
     * Cancel a shipment
     */
    public function cancelShipment(string $trackingNumber): bool;

    /**
     * Get tracking information
     */
    public function getTracking(string $trackingNumber): TrackingData;

    /**
     * Get shipping rates
     */
    public function getRates(ShipmentData $data): CarrierRateData;

    /**
     * Schedule a pickup
     */
    public function schedulePickup(string $trackingNumber, Carbon $date): bool;

    /**
     * Validate an address
     */
    public function validateAddress(array $address): bool;

    /**
     * Get carrier service areas
     */
    public function getServiceAreas(): array;

    /**
     * Check if carrier supports COD
     */
    public function supportsCOD(): bool;

    /**
     * Check if carrier supports tracking
     */
    public function supportsTracking(): bool;

    /**
     * Test carrier connection
     */
    public function testConnection(): bool;
}