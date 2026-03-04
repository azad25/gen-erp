<?php

namespace App\Domain\Logistics\Http\Controllers;

use App\Domain\Logistics\Contracts\CODManagementServiceInterface;
use App\Domain\Logistics\Http\Resources\ShipmentResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CODController extends Controller
{
    public function __construct(
        private readonly CODManagementServiceInterface $codService
    ) {}

    public function calculateCharge(Request $request): JsonResponse
    {
        $request->validate([
            'cod_amount' => 'required|numeric|min:0',
            'carrier_id' => 'required|integer|exists:carriers,id',
        ]);

        $charge = $this->codService->calculateCODCharge(
            $request->cod_amount,
            $request->carrier_id
        );

        return response()->json([
            'success' => true,
            'data' => [
                'cod_amount' => $request->cod_amount,
                'cod_charge' => $charge,
                'net_amount' => $request->cod_amount - $charge,
            ]
        ]);
    }

    public function markCollected(Request $request, int $shipmentId): JsonResponse
    {
        $request->validate([
            'collected_amount' => 'required|numeric|min:0',
            'collected_at' => 'nullable|date',
        ]);

        try {
            $shipment = $this->codService->markCODCollected(
                $shipmentId,
                $request->collected_amount,
                $request->collected_at ? \Carbon\Carbon::parse($request->collected_at) : null
            );

            return response()->json([
                'success' => true,
                'message' => __('logistics.cod.marked_collected'),
                'data' => new ShipmentResource($shipment)
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function settle(Request $request, int $carrierId): JsonResponse
    {
        $request->validate([
            'shipment_ids' => 'nullable|array',
            'shipment_ids.*' => 'integer|exists:shipments,id',
        ]);

        $result = $this->codService->settleCODWithCarrier(
            $carrierId,
            $request->shipment_ids
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result
        ]);
    }

    public function summary(Request $request, int $carrierId): JsonResponse
    {
        // Check if carrier belongs to current company
        $companyId = auth()->user()->last_active_company_id;
        $carrier = \App\Domain\Logistics\Models\Carrier::where('id', $carrierId)
            ->where('company_id', $companyId)
            ->first();

        if (!$carrier) {
            return response()->json([
                'success' => false,
                'message' => 'Carrier not found'
            ], 404);
        }

        $filters = $request->only(['date_from', 'date_to']);
        $summary = $this->codService->getCODSummary($carrierId, $filters);

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    public function pendingCollection(int $carrierId): JsonResponse
    {
        $pendingShipments = $this->codService->getPendingCODShipments($carrierId);

        return response()->json([
            'success' => true,
            'data' => ShipmentResource::collection($pendingShipments)
        ]);
    }

    public function pendingSettlement(int $carrierId): JsonResponse
    {
        $unsettledShipments = $this->codService->getUnsettledCODShipments($carrierId);

        return response()->json([
            'success' => true,
            'data' => ShipmentResource::collection($unsettledShipments)
        ]);
    }

    public function report(Request $request, int $carrierId): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to']);
        $report = $this->codService->generateCODReport($carrierId, $filters);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    public function syncStatus(int $carrierId): JsonResponse
    {
        $result = $this->codService->syncCODStatusWithCarrier($carrierId);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result
        ]);
    }
}