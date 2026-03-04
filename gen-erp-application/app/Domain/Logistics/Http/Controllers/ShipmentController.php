<?php

namespace App\Domain\Logistics\Http\Controllers;

use App\Domain\Logistics\Contracts\ShipmentServiceInterface;
use App\Domain\Logistics\DTOs\ShipmentData;
use App\Domain\Logistics\Http\Requests\CreateShipmentRequest;
use App\Domain\Logistics\Http\Requests\UpdateShipmentRequest;
use App\Domain\Logistics\Http\Resources\ShipmentResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function __construct(
        private readonly ShipmentServiceInterface $shipmentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status', 'carrier_id', 'payment_method', 'delivery_type',
            'date_from', 'date_to', 'search'
        ]);

        $shipments = $this->shipmentService->listShipments(
            $filters,
            $request->get('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'data' => ShipmentResource::collection($shipments->items()),
            'meta' => [
                'current_page' => $shipments->currentPage(),
                'last_page' => $shipments->lastPage(),
                'per_page' => $shipments->perPage(),
                'total' => $shipments->total(),
            ]
        ]);
    }

    public function store(CreateShipmentRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['company_id'] = auth()->user()->last_active_company_id;
        $validatedData['created_by'] = auth()->id();
        
        $shipmentData = ShipmentData::fromArray($validatedData);
        $shipment = $this->shipmentService->createShipment($shipmentData);

        return response()->json([
            'success' => true,
            'message' => __('logistics.shipment.created'),
            'data' => new ShipmentResource($shipment)
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $shipment = $this->shipmentService->getShipment($id);

        return response()->json([
            'success' => true,
            'data' => new ShipmentResource($shipment)
        ]);
    }

    public function update(UpdateShipmentRequest $request, int $id): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['company_id'] = auth()->user()->last_active_company_id;
        
        // Get existing shipment to preserve required fields not in update request
        $existingShipment = $this->shipmentService->getShipment($id);
        
        // Merge with existing data for required fields that might not be in update request
        $mergedData = array_merge([
            'carrier_id' => $existingShipment->carrier_id,
            'customer_id' => $existingShipment->customer_id,
            'sender_name' => $existingShipment->sender_name,
            'sender_phone' => $existingShipment->sender_phone,
            'sender_address' => $existingShipment->sender_address,
            'sender_city' => $existingShipment->sender_city,
            'recipient_name' => $existingShipment->recipient_name,
            'recipient_phone' => $existingShipment->recipient_phone,
            'recipient_address' => $existingShipment->recipient_address,
            'recipient_city' => $existingShipment->recipient_city,
        ], $validatedData);
        
        $shipmentData = ShipmentData::fromArray($mergedData);
        $shipment = $this->shipmentService->updateShipment($id, $shipmentData);

        return response()->json([
            'success' => true,
            'message' => __('logistics.shipment.updated'),
            'data' => new ShipmentResource($shipment)
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->shipmentService->cancelShipment($id);

        return response()->json([
            'success' => true,
            'message' => __('logistics.shipment.cancelled')
        ]);
    }

    public function bulkCreate(Request $request): JsonResponse
    {
        $request->validate([
            'shipments' => 'required|array|min:1|max:100',
            'shipments.*' => 'required|array',
        ]);

        // Add company_id and created_by to each shipment
        $shipments = collect($request->shipments)->map(function ($shipment) {
            $shipment['company_id'] = auth()->user()->last_active_company_id;
            $shipment['created_by'] = auth()->id();
            return $shipment;
        })->toArray();

        $results = $this->shipmentService->bulkCreateShipments($shipments);

        return response()->json([
            'success' => true,
            'message' => __('logistics.shipment.bulk_created'),
            'data' => $results
        ]);
    }

    public function generateLabel(int $id): JsonResponse
    {
        $labelUrl = $this->shipmentService->generateShippingLabel($id);

        return response()->json([
            'success' => true,
            'data' => ['label_url' => $labelUrl]
        ]);
    }

    public function schedulePickup(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'pickup_date' => 'required|date|after:today',
            'pickup_time_slot' => 'required|string',
            'special_instructions' => 'nullable|string|max:500',
        ]);

        $result = $this->shipmentService->schedulePickup(
            $id,
            \Carbon\Carbon::parse($request->pickup_date),
            $request->pickup_time_slot,
            $request->special_instructions
        );

        return response()->json([
            'success' => true,
            'message' => __('logistics.shipment.pickup_scheduled'),
            'data' => $result
        ]);
    }
}