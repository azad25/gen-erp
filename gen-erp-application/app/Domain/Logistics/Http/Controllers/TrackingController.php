<?php

namespace App\Domain\Logistics\Http\Controllers;

use App\Domain\Logistics\Contracts\TrackingServiceInterface;
use App\Domain\Logistics\Http\Resources\TrackingEventResource;
use App\Domain\Logistics\Http\Resources\ShipmentResource;
use App\Domain\Logistics\Models\Shipment;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function __construct(
        private readonly TrackingServiceInterface $trackingService
    ) {}

    public function track(string $trackingNumber): JsonResponse
    {
        try {
            $trackingHistory = $this->trackingService->getTrackingHistoryByNumber($trackingNumber);
            $shipment = Shipment::where('tracking_number', $trackingNumber)->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'shipment' => new ShipmentResource($shipment),
                    'tracking_events' => TrackingEventResource::collection($trackingHistory),
                    'estimated_delivery' => $this->trackingService->estimateDeliveryTime($shipment->id)?->format('Y-m-d H:i:s'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('logistics.tracking.not_found')
            ], 404);
        }
    }

    public function history(int $shipmentId): JsonResponse
    {
        $trackingHistory = $this->trackingService->getTrackingHistory($shipmentId);

        return response()->json([
            'success' => true,
            'data' => TrackingEventResource::collection($trackingHistory)
        ]);
    }

    public function updateStatus(Request $request, int $shipmentId): JsonResponse
    {
        $request->validate([
            'status' => 'required|string',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $status = \App\Domain\Logistics\Enums\ShipmentStatus::from($request->status);
        
        $trackingEvent = $this->trackingService->updateShipmentStatus(
            $shipmentId,
            $status,
            $request->location,
            $request->description
        );

        return response()->json([
            'success' => true,
            'message' => __('logistics.tracking.status_updated'),
            'data' => new TrackingEventResource($trackingEvent)
        ]);
    }

    public function syncWithCarrier(int $shipmentId): JsonResponse
    {
        $success = $this->trackingService->syncWithCarrier($shipmentId);

        return response()->json([
            'success' => $success,
            'message' => $success 
                ? __('logistics.tracking.sync_success')
                : __('logistics.tracking.sync_failed')
        ]);
    }

    public function bulkSync(Request $request): JsonResponse
    {
        $request->validate([
            'shipment_ids' => 'nullable|array',
            'shipment_ids.*' => 'integer|exists:shipments,id',
        ]);

        $results = $this->trackingService->bulkSyncWithCarriers($request->shipment_ids);

        return response()->json([
            'success' => true,
            'message' => __('logistics.tracking.bulk_sync_completed'),
            'data' => $results
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $filters = $request->only(['company_id', 'date_from', 'date_to']);
        $statistics = $this->trackingService->getDeliveryStatistics($filters);

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    public function publicTrack(string $tenant, string $trackingNumber): JsonResponse
    {
        try {
            $trackingHistory = $this->trackingService->getTrackingHistoryByNumber($trackingNumber);
            $shipment = Shipment::where('tracking_number', $trackingNumber)->firstOrFail();

            // Return limited information for public tracking
            return response()->json([
                'success' => true,
                'data' => [
                    'tracking_number' => $shipment->tracking_number,
                    'status' => $shipment->status->label(),
                    'recipient_name' => $shipment->recipient_name,
                    'recipient_city' => $shipment->recipient_city,
                    'estimated_delivery' => $this->trackingService->estimateDeliveryTime($shipment->id)?->format('Y-m-d'),
                    'tracking_events' => $trackingHistory->map(function ($event) {
                        return [
                            'status' => $event->status->label(),
                            'location' => $event->location,
                            'description' => $event->description,
                            'event_time' => $event->event_time->format('Y-m-d H:i:s'),
                        ];
                    }),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('logistics.tracking.not_found')
            ], 404);
        }
    }
}