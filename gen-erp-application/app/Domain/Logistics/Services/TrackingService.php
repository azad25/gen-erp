<?php

namespace App\Domain\Logistics\Services;

use App\Domain\Logistics\Contracts\TrackingServiceInterface;
use App\Domain\Logistics\Models\Shipment;
use App\Domain\Logistics\Models\TrackingEvent;
use App\Domain\Logistics\Enums\ShipmentStatus;
use App\Domain\Logistics\Events\ShipmentStatusUpdated;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class TrackingService implements TrackingServiceInterface
{
    public function updateShipmentStatus(int $shipmentId, ShipmentStatus $status, string $location = null, string $description = null): TrackingEvent
    {
        $shipment = Shipment::findOrFail($shipmentId);
        
        // Create tracking event
        $trackingEvent = $shipment->trackingEvents()->create([
            'status' => $status,
            'location' => $location,
            'description' => $description ?? $status->label(),
            'event_time' => now(),
        ]);

        // Update shipment status
        $shipment->update(['status' => $status]);

        // Set delivery date if delivered
        if ($status === ShipmentStatus::DELIVERED) {
            $shipment->update(['actual_delivery_date' => now()]);
        }

        // Fire event
        event(new ShipmentStatusUpdated($shipment, $status, $trackingEvent));

        return $trackingEvent;
    }

    public function getTrackingHistory(int $shipmentId): Collection
    {
        return TrackingEvent::where('shipment_id', $shipmentId)
            ->orderBy('event_time', 'desc')
            ->get();
    }

    public function getTrackingHistoryByNumber(string $trackingNumber): Collection
    {
        $shipment = Shipment::where('tracking_number', $trackingNumber)->firstOrFail();
        return $this->getTrackingHistory($shipment->id);
    }

    public function getLatestStatus(int $shipmentId): ?TrackingEvent
    {
        return TrackingEvent::where('shipment_id', $shipmentId)
            ->latest('event_time')
            ->first();
    }

    public function syncWithCarrier(int $shipmentId): bool
    {
        $shipment = Shipment::with('carrier')->findOrFail($shipmentId);
        
        if (!$shipment->carrier_tracking_number || !$shipment->carrier->supports_tracking) {
            return false;
        }

        try {
            $carrierApi = $shipment->carrier->getApiInstance();
            $trackingData = $carrierApi->getTrackingInfo($shipment->carrier_tracking_number);
            
            // Process tracking events from carrier
            foreach ($trackingData['events'] ?? [] as $event) {
                $this->processCarrierEvent($shipment, $event);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to sync tracking with carrier: " . $e->getMessage(), [
                'shipment_id' => $shipmentId,
                'carrier' => $shipment->carrier->name,
            ]);
            return false;
        }
    }

    public function bulkSyncWithCarriers(array $shipmentIds = null): array
    {
        $query = Shipment::with('carrier')
            ->whereNotNull('carrier_tracking_number')
            ->whereHas('carrier', function ($q) {
                $q->where('supports_tracking', true);
            });

        if ($shipmentIds) {
            $query->whereIn('id', $shipmentIds);
        }

        $shipments = $query->get();
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($shipments as $shipment) {
            if ($this->syncWithCarrier($shipment->id)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed to sync shipment {$shipment->tracking_number}";
            }
        }

        return $results;
    }

    public function estimateDeliveryTime(int $shipmentId): ?\Carbon\Carbon
    {
        $shipment = Shipment::findOrFail($shipmentId);
        
        if ($shipment->expected_delivery_date) {
            return $shipment->expected_delivery_date;
        }

        // Calculate based on delivery type and current status
        $baseDays = $shipment->delivery_type->expectedDays();
        
        // Adjust based on current status
        $adjustment = match ($shipment->status) {
            ShipmentStatus::PENDING => 0,
            ShipmentStatus::PICKED_UP => -1,
            ShipmentStatus::IN_TRANSIT => -2,
            ShipmentStatus::OUT_FOR_DELIVERY => 0, // Should be today
            default => 0,
        };

        return now()->addDays($baseDays + $adjustment);
    }

    public function getDeliveryStatistics(array $filters = []): array
    {
        $companyId = $filters['company_id'] ?? auth()->user()->currentCompany->id;
        
        $query = Shipment::forCompany($companyId);

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $totalShipments = $query->count();
        $deliveredShipments = $query->clone()->where('status', ShipmentStatus::DELIVERED)->get();
        
        $onTimeDeliveries = $deliveredShipments->filter(function ($shipment) {
            return $shipment->actual_delivery_date <= $shipment->expected_delivery_date;
        })->count();

        $avgDeliveryTime = $deliveredShipments->avg(function ($shipment) {
            return $shipment->created_at->diffInDays($shipment->actual_delivery_date);
        });

        return [
            'total_shipments' => $totalShipments,
            'delivered_shipments' => $deliveredShipments->count(),
            'delivery_rate' => $totalShipments > 0 ? round(($deliveredShipments->count() / $totalShipments) * 100, 2) : 0,
            'on_time_deliveries' => $onTimeDeliveries,
            'on_time_rate' => $deliveredShipments->count() > 0 ? round(($onTimeDeliveries / $deliveredShipments->count()) * 100, 2) : 0,
            'avg_delivery_days' => round($avgDeliveryTime ?? 0, 1),
        ];
    }

    protected function processCarrierEvent(Shipment $shipment, array $eventData): void
    {
        // Map carrier status to our status
        $status = $this->mapCarrierStatus($eventData['status'] ?? '');
        
        if (!$status) {
            return; // Skip unknown statuses
        }

        // Check if we already have this event
        $existingEvent = TrackingEvent::where('shipment_id', $shipment->id)
            ->where('carrier_data->event_id', $eventData['id'] ?? null)
            ->first();

        if ($existingEvent) {
            return; // Skip duplicate events
        }

        // Create tracking event
        $this->updateShipmentStatus(
            $shipment->id,
            $status,
            $eventData['location'] ?? null,
            $eventData['description'] ?? $status->label()
        );
    }

    protected function mapCarrierStatus(string $carrierStatus): ?ShipmentStatus
    {
        return match (strtolower($carrierStatus)) {
            'picked_up', 'pickup', 'collected' => ShipmentStatus::PICKED_UP,
            'in_transit', 'transit', 'on_the_way' => ShipmentStatus::IN_TRANSIT,
            'out_for_delivery', 'out_delivery', 'delivering' => ShipmentStatus::OUT_FOR_DELIVERY,
            'delivered', 'delivery_completed' => ShipmentStatus::DELIVERED,
            'failed', 'delivery_failed', 'undelivered' => ShipmentStatus::FAILED,
            'returned', 'return_to_sender' => ShipmentStatus::RETURNED,
            'cancelled', 'canceled' => ShipmentStatus::CANCELLED,
            default => null,
        };
    }
}