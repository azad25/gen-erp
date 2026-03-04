<?php

namespace App\Domain\Logistics\DTOs;

use App\Domain\Logistics\Enums\ShipmentStatus;
use Carbon\Carbon;

class TrackingData
{
    public function __construct(
        public readonly string $trackingNumber,
        public readonly ShipmentStatus $status,
        public readonly string $statusDescription,
        public readonly ?string $location = null,
        public readonly ?Carbon $eventTime = null,
        public readonly ?Carbon $estimatedDelivery = null,
        public readonly array $events = [],
        public readonly array $carrierData = [],
    ) {}

    public static function fromCarrierResponse(array $response, string $trackingNumber): self
    {
        // This will be implemented differently for each carrier
        return new self(
            trackingNumber: $trackingNumber,
            status: self::mapCarrierStatus($response['status'] ?? 'unknown'),
            statusDescription: $response['status_description'] ?? 'Status unknown',
            location: $response['location'] ?? null,
            eventTime: isset($response['event_time']) ? Carbon::parse($response['event_time']) : null,
            estimatedDelivery: isset($response['estimated_delivery']) ? Carbon::parse($response['estimated_delivery']) : null,
            events: $response['events'] ?? [],
            carrierData: $response,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            trackingNumber: $data['tracking_number'],
            status: ShipmentStatus::from($data['status']),
            statusDescription: $data['status_description'],
            location: $data['location'] ?? null,
            eventTime: isset($data['event_time']) ? Carbon::parse($data['event_time']) : null,
            estimatedDelivery: isset($data['estimated_delivery']) ? Carbon::parse($data['estimated_delivery']) : null,
            events: $data['events'] ?? [],
            carrierData: $data['carrier_data'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'tracking_number' => $this->trackingNumber,
            'status' => $this->status->value,
            'status_description' => $this->statusDescription,
            'location' => $this->location,
            'event_time' => $this->eventTime?->toISOString(),
            'estimated_delivery' => $this->estimatedDelivery?->toISOString(),
            'events' => $this->events,
            'carrier_data' => $this->carrierData,
        ];
    }

    private static function mapCarrierStatus(string $carrierStatus): ShipmentStatus
    {
        // Generic mapping - each carrier integration will override this
        return match (strtolower($carrierStatus)) {
            'pending', 'created', 'booked' => ShipmentStatus::PENDING,
            'picked_up', 'collected', 'pickup' => ShipmentStatus::PICKED_UP,
            'in_transit', 'transit', 'on_the_way' => ShipmentStatus::IN_TRANSIT,
            'out_for_delivery', 'delivery', 'delivering' => ShipmentStatus::OUT_FOR_DELIVERY,
            'delivered', 'completed' => ShipmentStatus::DELIVERED,
            'failed', 'exception', 'problem' => ShipmentStatus::FAILED,
            'returned', 'return' => ShipmentStatus::RETURNED,
            'cancelled', 'canceled' => ShipmentStatus::CANCELLED,
            default => ShipmentStatus::PENDING,
        };
    }

    public function isDelivered(): bool
    {
        return $this->status === ShipmentStatus::DELIVERED;
    }

    public function isFailed(): bool
    {
        return $this->status === ShipmentStatus::FAILED;
    }

    public function isInTransit(): bool
    {
        return in_array($this->status, [
            ShipmentStatus::PICKED_UP,
            ShipmentStatus::IN_TRANSIT,
            ShipmentStatus::OUT_FOR_DELIVERY,
        ]);
    }

    public function getStatusColor(): string
    {
        return $this->status->color();
    }

    public function getFormattedEventTime(): ?string
    {
        return $this->eventTime?->format('M d, Y h:i A');
    }

    public function getFormattedEstimatedDelivery(): ?string
    {
        return $this->estimatedDelivery?->format('M d, Y');
    }
}