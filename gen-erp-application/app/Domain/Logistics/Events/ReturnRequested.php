<?php

namespace App\Domain\Logistics\Events;

use App\Domain\Logistics\Models\ShipmentReturn;
use App\Domain\Notification\Contracts\NotifiableEvent;
use App\Domain\Notification\DTOs\NotificationPayload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReturnRequested implements NotifiableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ShipmentReturn $return
    ) {}

    public function toNotificationPayload(): NotificationPayload
    {
        return new NotificationPayload(
            domain: 'logistics',
            type: 'return.requested',
            titleKey: 'notifications.logistics.return.requested.title',
            bodyKey: 'notifications.logistics.return.requested.body',
            translationParams: [
                'return_number' => $this->return->return_number,
                'tracking_number' => $this->return->shipment->tracking_number,
                'reason' => $this->return->reason->label(),
            ],
            icon: 'rotate-ccw',
            color: 'warning',
            actionUrl: "/returns/{$this->return->id}",
            actionLabelKey: 'notifications.actions.review',
            channel: 'user',
            roleTarget: null,
            meta: [
                'return_id' => $this->return->id,
                'return_number' => $this->return->return_number,
                'shipment_id' => $this->return->shipment_id,
                'reason' => $this->return->reason->value,
            ]
        );
    }

    public function getRecipients(): \Illuminate\Support\Collection
    {
        $recipients = collect();

        // Add logistics team (users with return management permissions)
        // This would be implemented based on your permission system

        // Add shipment creator
        if ($this->return->shipment->createdBy) {
            $recipients->push($this->return->shipment->createdBy);
        }

        return $recipients;
    }
}