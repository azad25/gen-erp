<?php

namespace App\Domain\Logistics\Services;

use App\Domain\Logistics\Contracts\CODManagementServiceInterface;
use App\Domain\Logistics\Models\Shipment;
use App\Domain\Logistics\Models\Carrier;
use App\Domain\Logistics\Enums\ShipmentStatus;
use App\Domain\Logistics\Events\CODCollected;
use App\Domain\Logistics\Events\CODSettled;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CODManagementService implements CODManagementServiceInterface
{
    public function calculateCODCharge(float $codAmount, int $carrierId): float
    {
        $carrier = Carrier::findOrFail($carrierId);
        
        if (!$carrier->supports_cod) {
            return 0;
        }

        return round($codAmount * $carrier->cod_charge_percentage / 100, 2);
    }

    public function markCODCollected(int $shipmentId, float $collectedAmount, \Carbon\Carbon $collectedAt = null): Shipment
    {
        return DB::transaction(function () use ($shipmentId, $collectedAmount, $collectedAt) {
            $shipment = Shipment::findOrFail($shipmentId);

            if (!$shipment->isCOD()) {
                throw new \InvalidArgumentException("Shipment is not COD");
            }

            if ($shipment->status !== ShipmentStatus::DELIVERED) {
                throw new \InvalidArgumentException("COD can only be collected for delivered shipments");
            }

            $shipment->update([
                'cod_collected_amount' => $collectedAmount,
                'cod_collected_at' => $collectedAt ?? now(),
                'cod_status' => 'collected',
            ]);

            // Fire event
            event(new CODCollected($shipment, $collectedAmount));

            return $shipment->fresh();
        });
    }

    public function settleCODWithCarrier(int $carrierId, array $shipmentIds = null): array
    {
        return DB::transaction(function () use ($carrierId, $shipmentIds) {
            $carrier = Carrier::findOrFail($carrierId);

            $query = Shipment::where('carrier_id', $carrierId)
                ->where('payment_method', 'cod')
                ->where('cod_status', 'collected')
                ->whereNull('cod_settled_at');

            if ($shipmentIds) {
                $query->whereIn('id', $shipmentIds);
            }

            $shipments = $query->get();
            
            if ($shipments->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No COD shipments to settle',
                    'shipments_count' => 0,
                    'total_amount' => 0,
                ];
            }

            $totalCODAmount = $shipments->sum('cod_collected_amount');
            $totalCharges = $shipments->sum('cod_charge');
            $netAmount = $totalCODAmount - $totalCharges;

            // Update shipments as settled
            $shipments->each(function ($shipment) {
                $shipment->update([
                    'cod_status' => 'settled',
                    'cod_settled_at' => now(),
                ]);

                // Fire event for each shipment
                event(new CODSettled($shipment));
            });

            // Log settlement
            Log::info("COD settlement completed", [
                'carrier_id' => $carrierId,
                'shipments_count' => $shipments->count(),
                'total_cod_amount' => $totalCODAmount,
                'total_charges' => $totalCharges,
                'net_amount' => $netAmount,
            ]);

            return [
                'success' => true,
                'message' => 'COD settlement completed successfully',
                'shipments_count' => $shipments->count(),
                'total_cod_amount' => $totalCODAmount,
                'total_charges' => $totalCharges,
                'net_amount' => $netAmount,
                'settlement_date' => now()->toDateString(),
            ];
        });
    }

    public function getCODSummary(int $carrierId, array $filters = []): array
    {
        $query = Shipment::where('carrier_id', $carrierId)
            ->where('payment_method', 'cod');

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $totalCODShipments = $query->count();
        $deliveredCODShipments = $query->clone()->where('status', ShipmentStatus::DELIVERED)->count();
        $collectedCODShipments = $query->clone()->where('cod_status', 'collected')->count();
        $settledCODShipments = $query->clone()->where('cod_status', 'settled')->count();

        $totalCODAmount = $query->clone()->sum('cod_amount');
        $collectedAmount = $query->clone()->where('cod_status', 'collected')->sum('cod_collected_amount');
        $settledAmount = $query->clone()->where('cod_status', 'settled')->sum('cod_collected_amount');
        $totalCharges = $query->clone()->sum('cod_charge');

        $pendingCollection = $query->clone()
            ->where('status', ShipmentStatus::DELIVERED)
            ->where('cod_status', '!=', 'collected')
            ->sum('cod_amount');

        $pendingSettlement = $query->clone()
            ->where('cod_status', 'collected')
            ->whereNull('cod_settled_at')
            ->sum('cod_collected_amount');

        return [
            'total_cod_shipments' => $totalCODShipments,
            'delivered_cod_shipments' => $deliveredCODShipments,
            'collected_cod_shipments' => $collectedCODShipments,
            'settled_cod_shipments' => $settledCODShipments,
            'collection_rate' => $deliveredCODShipments > 0 ? round(($collectedCODShipments / $deliveredCODShipments) * 100, 2) : 0,
            'settlement_rate' => $collectedCODShipments > 0 ? round(($settledCODShipments / $collectedCODShipments) * 100, 2) : 0,
            'total_cod_amount' => $totalCODAmount,
            'collected_amount' => $collectedAmount,
            'settled_amount' => $settledAmount,
            'total_charges' => $totalCharges,
            'pending_collection' => $pendingCollection,
            'pending_settlement' => $pendingSettlement,
            'net_settled_amount' => $settledAmount - ($settledCODShipments > 0 ? $totalCharges * ($settledAmount / $collectedAmount) : 0),
        ];
    }

    public function getPendingCODShipments(int $carrierId): Collection
    {
        return Shipment::where('carrier_id', $carrierId)
            ->where('payment_method', 'cod')
            ->where('status', ShipmentStatus::DELIVERED)
            ->where(function ($query) {
                $query->whereNull('cod_status')
                      ->orWhere('cod_status', '!=', 'collected');
            })
            ->with(['customer'])
            ->orderBy('actual_delivery_date', 'desc')
            ->get();
    }

    public function getUnsettledCODShipments(int $carrierId): Collection
    {
        return Shipment::where('carrier_id', $carrierId)
            ->where('payment_method', 'cod')
            ->where('cod_status', 'collected')
            ->whereNull('cod_settled_at')
            ->with(['customer'])
            ->orderBy('cod_collected_at', 'desc')
            ->get();
    }

    public function generateCODReport(int $carrierId, array $filters = []): array
    {
        $summary = $this->getCODSummary($carrierId, $filters);
        $pendingShipments = $this->getPendingCODShipments($carrierId);
        $unsettledShipments = $this->getUnsettledCODShipments($carrierId);

        return [
            'summary' => $summary,
            'pending_collection' => $pendingShipments->map(function ($shipment) {
                return [
                    'tracking_number' => $shipment->tracking_number,
                    'customer_name' => $shipment->recipient_name,
                    'cod_amount' => $shipment->cod_amount,
                    'delivered_at' => $shipment->actual_delivery_date?->format('Y-m-d H:i:s'),
                    'days_pending' => $shipment->actual_delivery_date?->diffInDays(now()) ?? 0,
                ];
            }),
            'pending_settlement' => $unsettledShipments->map(function ($shipment) {
                return [
                    'tracking_number' => $shipment->tracking_number,
                    'customer_name' => $shipment->recipient_name,
                    'cod_amount' => $shipment->cod_amount,
                    'collected_amount' => $shipment->cod_collected_amount,
                    'cod_charge' => $shipment->cod_charge,
                    'collected_at' => $shipment->cod_collected_at?->format('Y-m-d H:i:s'),
                    'days_pending' => $shipment->cod_collected_at?->diffInDays(now()) ?? 0,
                ];
            }),
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    public function syncCODStatusWithCarrier(int $carrierId): array
    {
        $carrier = Carrier::findOrFail($carrierId);
        
        if (!$carrier->supports_cod) {
            return [
                'success' => false,
                'message' => 'Carrier does not support COD',
                'synced_count' => 0,
            ];
        }

        $pendingShipments = $this->getPendingCODShipments($carrierId);
        $syncedCount = 0;

        foreach ($pendingShipments as $shipment) {
            try {
                $carrierApi = $carrier->getApiInstance();
                $codStatus = $carrierApi->getCODStatus($shipment->carrier_tracking_number);
                
                if ($codStatus['collected']) {
                    $this->markCODCollected(
                        $shipment->id,
                        $codStatus['collected_amount'],
                        \Carbon\Carbon::parse($codStatus['collected_at'])
                    );
                    $syncedCount++;
                }
            } catch (\Exception $e) {
                Log::warning("Failed to sync COD status for shipment {$shipment->tracking_number}: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'message' => "COD status synced for {$syncedCount} shipments",
            'synced_count' => $syncedCount,
        ];
    }
}