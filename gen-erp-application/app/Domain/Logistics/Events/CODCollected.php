<?php

namespace App\Domain\Logistics\Events;

use App\Domain\Logistics\Models\Shipment;
use App\Domain\Notification\Contracts\NotifiableEvent;
use App\Domain\Notification\DTOs\NotificationPayload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CODCollected implements NotifiableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Shipment $shipment,
        public readonly float $collectedAmount
    ) {}

    public function toNotificationPayload(): NotificationPayload
    {
        return new NotificationPayload(
            domain: 'logistics',
            type: 'cod.collected',
            titleKey: 'notifications.logistics.cod.collected.title',
            bodyKey: 'notifications.logistics.cod.collected.body',
            translationParams: [
                'tracking_number' => $this->shipment->tracking_number,
                'amount' => number_format($this->collectedAmount, 2),
                'currency' => 'BDT',
            ],
            icon: 'dollar-sign',
            color: 'success',
            actionUrl: "/shipments/{$this->shipment->id}",
            actionLabelKey: 'notifications.actions.view',
            channel: 'user',
            roleTarget: null,
            meta: [
                'shipment_id' => $this->shipment->id,
                'tracking_number' => $this->shipment->tracking_number,
                'collected_amount' => $this->collectedAmount,
                'cod_amount' => $this->shipment->cod_amount,
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

        // Add finance team (users with COD management permissions)
        // This would be implemented based on your permission system

        return $recipients;
    }
}