<?php

namespace App\Domain\Logistics\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'return_number' => $this->return_number,
            'status' => $this->status,
            'reason' => [
                'value' => $this->reason->value,
                'label' => $this->reason->label(),
            ],
            'reason_details' => $this->reason_details,
            'images' => $this->images,
            
            // Shipment information
            'shipment' => [
                'id' => $this->shipment->id,
                'tracking_number' => $this->shipment->tracking_number,
                'recipient_name' => $this->shipment->recipient_name,
            ],
            
            // Request information
            'requested_by' => [
                'id' => $this->requestedBy->id,
                'name' => $this->requestedBy->name,
                'email' => $this->requestedBy->email,
            ],
            'requested_at' => $this->requested_at->format('Y-m-d H:i:s'),
            
            // Approval information
            'approved_by' => $this->when($this->approvedBy, [
                'id' => $this->approvedBy?->id,
                'name' => $this->approvedBy?->name,
                'email' => $this->approvedBy?->email,
            ]),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            
            // Return shipment information
            'return_tracking_number' => $this->return_tracking_number,
            'return_carrier' => $this->when($this->returnCarrier, [
                'id' => $this->returnCarrier?->id,
                'name' => $this->returnCarrier?->name,
            ]),
            
            // Refund information
            'refund_amount' => $this->refund_amount,
            'refund_method' => $this->refund_method,
            'refunded_at' => $this->refunded_at?->format('Y-m-d H:i:s'),
            
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}