<?php

namespace App\Domain\Logistics\Events;

use App\Domain\Logistics\Models\ShipmentReturn;
use App\Domain\Notification\Contracts\NotifiableEvent;
use App\Domain\Notification\DTOs\NotificationPayload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReturnRejected implements NotifiableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ShipmentReturn $return,
        public readonly ?string $rejectionReason = null
    ) {}

    public function toNotificationPayload(): NotificationPayload
    {
        return new NotificationPayload(
            domain: 'logistics',
            type: 'return.rejected',
            titleKey: 'notifications.logistics.return.rejected.title',
            bodyKey: 'notifications.logistics.return.rejected.body',
            translationParams: [
                'return_number' => $this->return->return_number,
                'tracking_number' => $this->return->shipment->tracking_number,
                'reason' => $this->rejectionReason ?? 'No reason provided',
            ],
            icon: 'x-circle',
            color: 'error',
            actionUrl: "/returns/{$this->return->id}",
            actionLabelKey: 'notifications.actions.view',
            channel: 'user',
            roleTarget: null,
            meta: [
                'return_id' => $this->return->id,
                'return_number' => $this->return->return_number,
                'shipment_id' => $this->return->shipment_id,
                'rejection_reason' => $this->rejectionReason,
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