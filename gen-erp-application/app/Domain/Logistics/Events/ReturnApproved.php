<?php

namespace App\Domain\Logistics\Events;

use App\Domain\Logistics\Models\ShipmentReturn;
use App\Domain\Notification\Contracts\NotifiableEvent;
use App\Domain\Notification\DTOs\NotificationPayload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReturnApproved implements NotifiableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ShipmentReturn $return
    ) {}

    public function toNotificationPayload(): NotificationPayload
    {
        return new NotificationPayload(
            domain: 'logistics',
            type: 'return.approved',
            titleKey: 'notifications.logistics.return.approved.title',
            bodyKey: 'notifications.logistics.return.approved.body',
            translationParams: [
                'return_number' => $this->return->return_number,
                'tracking_number' => $this->return->shipment->tracking_number,
            ],
            icon: 'check-circle',
            color: 'success',
            actionUrl: "/returns/{$this->return->id}",
            actionLabelKey: 'notifications.actions.view',
            channel: 'user',
            roleTarget: null,
            meta: [
                'return_id' => $this->return->id,
                'return_number' => $this->return->return_number,
                'shipment_id' => $this->return->shipment_id,
            ]
        );
    }

    public function getRecipients(): \Illuminate\Support\Collection
    {
        $recipients = collect();

        // Add return requester
        if ($this->return->requestedBy) {
            $recipients->push($this->return->requestedBy);
        }

        return $recipients;
    }
}