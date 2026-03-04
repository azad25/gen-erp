<?php

namespace App\Domain\Logistics\Events;

use App\Domain\Logistics\Models\Shipment;
use App\Domain\Notification\Contracts\NotifiableEvent;
use App\Domain\Notification\DTOs\NotificationPayload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CODSettled implements NotifiableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Shipment $shipment
    ) {}

    public function toNotificationPayload(): NotificationPayload
    {
        $netAmount = $this->shipment->cod_collected_amount - $this->shipment->cod_charge;
        
        return new NotificationPayload(
            domain: 'logistics',
            type: 'cod.settled',
            titleKey: 'notifications.logistics.cod.settled.title',
            bodyKey: 'notifications.logistics.cod.settled.body',
            translationParams: [
                'tracking_number' => $this->shipment->tracking_number,
                'amount' => number_format($netAmount, 2),
                'currency' => 'BDT',
                'carrier' => $this->shipment->carrier->name,
            ],
            icon: 'check-circle',
            color: 'info',
            actionUrl: "/shipments/{$this->shipment->id}",
            actionLabelKey: 'notifications.actions.view',
            channel: 'user',
            roleTarget: null,
            meta: [
                'shipment_id' => $this->shipment->id,
                'tracking_number' => $this->shipment->tracking_number,
                'collected_amount' => $this->shipment->cod_collected_amount,
                'cod_charge' => $this->shipment->cod_charge,
                'net_amount' => $netAmount,
                'carrier_id' => $this->shipment->carrier_id,
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