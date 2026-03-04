<?php

namespace App\Domain\Logistics\Events;

use App\Domain\Logistics\Models\Shipment;
use App\Domain\Logistics\Models\TrackingEvent;
use App\Domain\Logistics\Enums\ShipmentStatus;
use App\Domain\Notification\Contracts\NotifiableEvent;
use App\Domain\Notification\DTOs\NotificationPayload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentStatusUpdated implements NotifiableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Shipment $shipment,
        public readonly ShipmentStatus $status,
        public readonly TrackingEvent $trackingEvent
    ) {}

    public function toNotificationPayload(): NotificationPayload
    {
        return new NotificationPayload(
            domain: 'logistics',
            type: 'shipment.status_updated',
            titleKey: 'notifications.logistics.shipment.status_updated.title',
            bodyKey: 'notifications.logistics.shipment.status_updated.body',
            translationParams: [
                'tracking_number' => $this->shipment->tracking_number,
                'status' => $this->status->label(),
                'location' => $this->trackingEvent->location ?? 'Unknown',
            ],
            icon: $this->getStatusIcon(),
            color: $this->getStatusColor(),
            actionUrl: "/shipments/{$this->shipment->id}",
            actionLabelKey: 'notifications.actions.view',
            channel: 'user',
            roleTarget: null,
            meta: [
                'shipment_id' => $this->shipment->id,
                'tracking_number' => $this->shipment->tracking_number,
                'status' => $this->status->value,
                'location' => $this->trackingEvent->location,
            ]
        );
    }

    public function getRecipients(): \Illuminate\Support\Collection
    {
        $recipients = collect();

        // Add shipment creator
        if ($this->shipment->createdBy) {
            $recipients->push($this->shipment->createdBy);
        }

        return $recipients;
    }

    private function getStatusIcon(): string
    {
        return match ($this->status) {
            ShipmentStatus::PICKED_UP => 'package',
            ShipmentStatus::IN_TRANSIT => 'truck',
            ShipmentStatus::OUT_FOR_DELIVERY => 'map-pin',
            ShipmentStatus::DELIVERED => 'check-circle',
            ShipmentStatus::FAILED => 'x-circle',
            ShipmentStatus::RETURNED => 'rotate-ccw',
            ShipmentStatus::CANCELLED => 'x',
            default => 'package',
        };
    }

    private function getStatusColor(): string
    {
        return match ($this->status) {
            ShipmentStatus::DELIVERED => 'success',
            ShipmentStatus::FAILED, ShipmentStatus::CANCELLED => 'error',
            ShipmentStatus::OUT_FOR_DELIVERY => 'warning',
            default => 'info',
        };
    }
}