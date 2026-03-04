<?php

namespace App\Domain\Logistics\Contracts;

use App\Domain\Logistics\Models\Shipment;
use Illuminate\Database\Eloquent\Collection;

interface CODManagementServiceInterface
{
    /**
     * Calculate COD charge for a given amount and carrier
     */
    public function calculateCODCharge(float $codAmount, int $carrierId): float;

    /**
     * Mark COD as collected
     */
    public function markCODCollected(int $shipmentId, float $collectedAmount, \Carbon\Carbon $collectedAt = null): Shipment;

    /**
     * Settle COD with carrier
     */
    public function settleCODWithCarrier(int $carrierId, array $shipmentIds = null): array;

    /**
     * Get COD summary for a carrier
     */
    public function getCODSummary(int $carrierId, array $filters = []): array;

    /**
     * Get pending COD shipments
     */
    public function getPendingCODShipments(int $carrierId): Collection;

    /**
     * Get unsettled COD shipments
     */
    public function getUnsettledCODShipments(int $carrierId): Collection;

    /**
     * Generate COD report
     */
    public function generateCODReport(int $carrierId, array $filters = []): array;

    /**
     * Sync COD status with carrier
     */
    public function syncCODStatusWithCarrier(int $carrierId): array;
}