<?php

namespace App\Domain\Logistics\Contracts;

use App\Domain\Logistics\Enums\ShipmentStatus;
use App\Domain\Logistics\Models\TrackingEvent;
use Illuminate\Database\Eloquent\Collection;

interface TrackingServiceInterface
{
    /**
     * Update shipment status and create tracking event
     */
    public function updateShipmentStatus(int $shipmentId, ShipmentStatus $status, string $location = null, string $description = null): TrackingEvent;

    /**
     * Get tracking history for a shipment
     */
    public function getTrackingHistory(int $shipmentId): Collection;

    /**
     * Get tracking history by tracking number
     */
    public function getTrackingHistoryByNumber(string $trackingNumber): Collection;

    /**
     * Get latest tracking status
     */
    public function getLatestStatus(int $shipmentId): ?TrackingEvent;

    /**
     * Sync tracking data with carrier
     */
    public function syncWithCarrier(int $shipmentId): bool;

    /**
     * Bulk sync tracking data with carriers
     */
    public function bulkSyncWithCarriers(array $shipmentIds = null): array;

    /**
     * Estimate delivery time
     */
    public function estimateDeliveryTime(int $shipmentId): ?\Carbon\Carbon;

    /**
     * Get delivery statistics
     */
    public function getDeliveryStatistics(array $filters = []): array;
}