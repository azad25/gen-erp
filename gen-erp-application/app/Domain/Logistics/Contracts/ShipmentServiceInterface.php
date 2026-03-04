<?php

namespace App\Domain\Logistics\Contracts;

use App\Domain\Logistics\DTOs\ShipmentData;
use App\Domain\Logistics\Models\Shipment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ShipmentServiceInterface
{
    /**
     * Create a new shipment
     */
    public function createShipment(ShipmentData $data): Shipment;

    /**
     * Create shipment from invoice
     */
    public function createFromInvoice(int $invoiceId, int $carrierId, array $additionalData = []): Shipment;

    /**
     * Update shipment
     */
    public function updateShipment(int $shipmentId, ShipmentData $data): Shipment;

    /**
     * Cancel shipment
     */
    public function cancelShipment(int $shipmentId, string $reason = null): bool;

    /**
     * Get shipment by ID
     */
    public function getShipment(int $shipmentId): Shipment;

    /**
     * Get shipment by tracking number
     */
    public function getShipmentByTracking(string $trackingNumber): Shipment;

    /**
     * List shipments with filters
     */
    public function listShipments(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Bulk create shipments
     */
    public function bulkCreateShipments(array $shipmentsData): \Illuminate\Support\Collection;

    /**
     * Schedule pickup for shipment
     */
    public function schedulePickup(int $shipmentId, \Carbon\Carbon $pickupDate): bool;

    /**
     * Generate shipping label
     */
    public function generateLabel(int $shipmentId): string;

    /**
     * Calculate shipping cost
     */
    public function calculateShippingCost(ShipmentData $data): float;

    /**
     * Get shipment statistics
     */
    public function getStatistics(array $filters = []): array;
}