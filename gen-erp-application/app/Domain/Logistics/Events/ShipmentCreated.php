<?php

namespace App\Domain\Logistics\Events;

use App\Domain\Logistics\Models\Shipment;
use App\Domain\Notification\Contracts\NotifiableEvent;
use App\Domain\Notification\DTOs\NotificationPayload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentCreated implements NotifiableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Shipment $shipment
    ) {}

    public function toNotificationPayload(): NotificationPayload
    {
        return new NotificationPayload(
            domain: 'logistics',
            type: 'shipment.created',
            titleKey: 'notifications.logistics.shipment.created.title',
            bodyKey: 'notifications.logistics.shipment.created.body',
            translationParams: [
                'tracking_number' => $this->shipment->tracking_number,
                'recipient_name' => $this->shipment->recipient_name,
            ],
            icon: 'truck',
            color: 'info',
            actionUrl: "/shipments/{$this->shipment->id}",
            actionLabelKey: 'notifications.actions.view',
            channel: 'user',
            roleTarget: null,
            meta: [
                'shipment_id' => $this->shipment->id,
                'tracking_number' => $this->shipment->tracking_number,
                'carrier' => $this->shipment->carrier->name,
            ]
        );
    }

    public function getRecipients(): \Illuminate\Support\Collection
    {
        // Notify the customer and relevant staff
        $recipients = collect();

        // Add shipment creator
        if ($this->shipment->createdBy) {
            $recipients->push($this->shipment->createdBy);
        }

        // Add logistics team (users with logistics permissions)
        // This would be implemented based on your permission system

        return $recipients;
    }
}