<?php

namespace App\Domain\Logistics\Events;

use App\Domain\Logistics\Models\Shipment;
use App\Domain\Notification\Contracts\NotifiableEvent;
use App\Domain\Notification\DTOs\NotificationPayload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentCancelled implements NotifiableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Shipment $shipment,
        public readonly ?string $reason = null
    ) {}

    public function toNotificationPayload(): NotificationPayload
    {
        return new NotificationPayload(
            domain: 'logistics',
            type: 'shipment.cancelled',
            titleKey: 'notifications.logistics.shipment.cancelled.title',
            bodyKey: 'notifications.logistics.shipment.cancelled.body',
            translationParams: [
                'tracking_number' => $this->shipment->tracking_number,
                'reason' => $this->reason ?? 'No reason provided',
            ],
            icon: 'x-circle',
            color: 'warning',
            actionUrl: "/shipments/{$this->shipment->id}",
            actionLabelKey: 'notifications.actions.view',
            channel: 'user',
            roleTarget: null,
            meta: [
                'shipment_id' => $this->shipment->id,
                'tracking_number' => $this->shipment->tracking_number,
                'reason' => $this->reason,
            ]
        );
    }

    public function getRecipients(): \Illuminate\Support\Collection
    {
        $recipients = collect();

        // Notify shipment creator
        if ($this->shipment->createdBy) {
            $recipients->push($this->shipment->createdBy);
        }

        // Add logistics team (users with logistics permissions)
        // This would be implemented based on your permission system

        return $recipients;
    }
}