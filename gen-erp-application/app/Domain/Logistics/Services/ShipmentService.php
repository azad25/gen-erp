<?php

namespace App\Domain\Logistics\Services;

use App\Domain\Logistics\Contracts\ShipmentServiceInterface;
use App\Domain\Logistics\DTOs\ShipmentData;
use App\Domain\Logistics\Models\Carrier;
use App\Domain\Logistics\Models\Shipment;
use App\Domain\Logistics\Enums\ShipmentStatus;
use App\Domain\Logistics\Events\ShipmentCreated;
use App\Domain\Logistics\Events\ShipmentCancelled;
use App\Domain\Logistics\Exceptions\ShipmentException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShipmentService implements ShipmentServiceInterface
{
    public function createShipment(ShipmentData $data): Shipment
    {
        return DB::transaction(function () use ($data) {
            // Validate carrier
            $carrier = Carrier::forCompany($data->companyId)
                ->active()
                ->findOrFail($data->carrierId);

            if (!$carrier->isConfigured()) {
                throw new ShipmentException("Carrier {$carrier->name} is not properly configured");
            }

            // Calculate shipping cost if not provided
            $shippingCost = $data->shippingCost > 0 
                ? $data->shippingCost 
                : $this->calculateShippingCost($data);

            // Calculate COD charge
            $codCharge = $data->isCOD() && $carrier->supports_cod 
                ? ($data->codAmount * $carrier->cod_charge_percentage / 100)
                : 0;

            // Create shipment
            $shipment = Shipment::create([
                'company_id' => $data->companyId,
                'carrier_id' => $data->carrierId,
                'invoice_id' => $data->invoiceId,
                'customer_id' => $data->customerId,
                'sender_name' => $data->senderName,
                'sender_phone' => $data->senderPhone,
                'sender_address' => $data->senderAddress,
                'sender_city' => $data->senderCity,
                'sender_area' => $data->senderArea,
                'sender_postcode' => $data->senderPostcode,
                'recipient_name' => $data->recipientName,
                'recipient_phone' => $data->recipientPhone,
                'recipient_email' => $data->recipientEmail,
                'recipient_address' => $data->recipientAddress,
                'recipient_city' => $data->recipientCity,
                'recipient_area' => $data->recipientArea,
                'recipient_postcode' => $data->recipientPostcode,
                'status' => $data->status,
                'delivery_type' => $data->deliveryType,
                'payment_method' => $data->paymentMethod,
                'cod_amount' => $data->codAmount,
                'shipping_cost' => $shippingCost,
                'cod_charge' => $codCharge,
                'total_cost' => $shippingCost + $codCharge,
                'weight' => $data->weight,
                'length' => $data->length,
                'width' => $data->width,
                'height' => $data->height,
                'special_instructions' => $data->specialInstructions,
                'package_description' => $data->packageDescription,
                'created_by' => $data->createdBy,
                'expected_delivery_date' => now()->addDays($data->deliveryType->expectedDays()),
            ]);

            // Create shipment items
            foreach ($data->items as $itemData) {
                $shipment->items()->create([
                    'product_variant_id' => $itemData['product_variant_id'] ?? null,
                    'invoice_item_id' => $itemData['invoice_item_id'] ?? null,
                    'product_name' => $itemData['product_name'] ?? $itemData['name'] ?? 'Unknown Product',
                    'sku' => $itemData['sku'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'] ?? $itemData['value'] ?? 0,
                    'total_price' => $itemData['total_price'] ?? ($itemData['quantity'] * ($itemData['value'] ?? 0)),
                    'weight' => $itemData['weight'] ?? null,
                    'description' => $itemData['description'] ?? null,
                ]);
            }

            // Create initial tracking event
            $shipment->trackingEvents()->create([
                'status' => $data->status->value,
                'description' => 'Shipment created',
                'event_time' => now(),
            ]);

            // Try to create shipment with carrier
            try {
                $carrierApi = $carrier->getApiInstance();
                $carrierResponse = $carrierApi->createShipment($data);
                
                $shipment->update([
                    'carrier_tracking_number' => $carrierResponse['tracking_number'] ?? null,
                    'carrier_response' => $carrierResponse,
                ]);
            } catch (\Exception $e) {
                Log::warning("Failed to create shipment with carrier {$carrier->name}: " . $e->getMessage());
                // Continue without carrier integration - can be retried later
            }

            // Fire event
            event(new ShipmentCreated($shipment));

            return $shipment->load(['items', 'carrier', 'customer']);
        });
    }

    public function createFromInvoice(int $invoiceId, int $carrierId, array $additionalData = []): Shipment
    {
        // This would integrate with the Invoice domain
        // For now, we'll throw an exception to indicate it needs implementation
        throw new ShipmentException("Invoice integration not yet implemented");
    }

    public function updateShipment(int $shipmentId, ShipmentData $data): Shipment
    {
        $shipment = Shipment::findOrFail($shipmentId);

        if ($shipment->status->isCompleted()) {
            throw new ShipmentException("Cannot update completed shipment");
        }

        $shipment->update($data->toArray());

        return $shipment->fresh(['items', 'carrier', 'customer']);
    }

    public function cancelShipment(int $shipmentId, string $reason = null): bool
    {
        return DB::transaction(function () use ($shipmentId, $reason) {
            $shipment = Shipment::findOrFail($shipmentId);

            if (!$shipment->canBeCancelled()) {
                throw new ShipmentException("Shipment cannot be cancelled in current status: {$shipment->status->label()}");
            }

            // Try to cancel with carrier
            try {
                $carrierApi = $shipment->carrier->getApiInstance();
                $carrierApi->cancelShipment($shipment->tracking_number);
            } catch (\Exception $e) {
                Log::warning("Failed to cancel shipment with carrier: " . $e->getMessage());
                // Continue with local cancellation
            }

            // Update status
            $shipment->updateStatus(
                ShipmentStatus::CANCELLED,
                null,
                $reason ?? 'Shipment cancelled'
            );

            // Fire event
            event(new ShipmentCancelled($shipment, $reason));

            return true;
        });
    }

    public function getShipment(int $shipmentId): Shipment
    {
        return Shipment::with(['items', 'carrier', 'customer', 'trackingEvents'])
            ->findOrFail($shipmentId);
    }

    public function getShipmentByTracking(string $trackingNumber): Shipment
    {
        return Shipment::with(['items', 'carrier', 'customer', 'trackingEvents'])
            ->where('tracking_number', $trackingNumber)
            ->firstOrFail();
    }

    public function listShipments(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Shipment::with(['carrier', 'customer'])
            ->forCompany($filters['company_id'] ?? auth()->user()->currentCompany->id);

        // Apply filters
        if (isset($filters['status'])) {
            $query->byStatus(ShipmentStatus::from($filters['status']));
        }

        if (isset($filters['carrier_id'])) {
            $query->where('carrier_id', $filters['carrier_id']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%")
                  ->orWhere('recipient_phone', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function bulkCreateShipments(array $shipmentsData): \Illuminate\Support\Collection
    {
        $shipments = collect();

        DB::transaction(function () use ($shipmentsData, &$shipments) {
            foreach ($shipmentsData as $shipmentData) {
                try {
                    $data = ShipmentData::fromArray($shipmentData);
                    $shipment = $this->createShipment($data);
                    $shipments->push($shipment);
                } catch (\Exception $e) {
                    Log::error("Failed to create bulk shipment: " . $e->getMessage(), $shipmentData);
                    // Continue with other shipments
                }
            }
        });

        return $shipments;
    }

    public function schedulePickup(int $shipmentId, \Carbon\Carbon $pickupDate): bool
    {
        $shipment = Shipment::findOrFail($shipmentId);

        try {
            $carrierApi = $shipment->carrier->getApiInstance();
            $success = $carrierApi->schedulePickup($shipment->tracking_number, $pickupDate);

            if ($success) {
                $shipment->update(['pickup_date' => $pickupDate->toDateString()]);
                
                $shipment->trackingEvents()->create([
                    'status' => 'pickup_scheduled',
                    'description' => "Pickup scheduled for {$pickupDate->format('M d, Y')}",
                    'event_time' => now(),
                ]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error("Failed to schedule pickup: " . $e->getMessage());
            return false;
        }
    }

    public function generateLabel(int $shipmentId): string
    {
        // This would generate a PDF shipping label
        // For now, return a placeholder
        throw new ShipmentException("Label generation not yet implemented");
    }

    public function calculateShippingCost(ShipmentData $data): float
    {
        $carrier = Carrier::findOrFail($data->carrierId);
        $weight = $data->getTotalWeight();
        
        return $carrier->calculateShippingCost($weight, $data->isCOD());
    }

    public function getStatistics(array $filters = []): array
    {
        $companyId = $filters['company_id'] ?? auth()->user()->currentCompany->id;
        
        $query = Shipment::forCompany($companyId);

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_shipments' => $query->count(),
            'pending_shipments' => $query->clone()->pending()->count(),
            'in_transit_shipments' => $query->clone()->inTransit()->count(),
            'delivered_shipments' => $query->clone()->byStatus(ShipmentStatus::DELIVERED)->count(),
            'failed_shipments' => $query->clone()->byStatus(ShipmentStatus::FAILED)->count(),
            'cod_shipments' => $query->clone()->cod()->count(),
            'total_shipping_cost' => $query->sum('shipping_cost'),
            'total_cod_amount' => $query->cod()->sum('cod_amount'),
        ];
    }
}