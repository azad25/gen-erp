<?php

namespace App\Domain\Logistics\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_number' => $this->tracking_number,
            'carrier_tracking_number' => $this->carrier_tracking_number,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
                'color' => $this->status?->color(),
            ],
            
            // Carrier information
            'carrier' => [
                'id' => $this->carrier->id,
                'name' => $this->carrier->name,
                'type' => $this->carrier->type?->value,
                'logo' => $this->carrier->logo,
            ],
            
            // Customer information
            'customer' => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
            ],
            
            // Recipient information
            'recipient' => [
                'name' => $this->recipient_name,
                'phone' => $this->recipient_phone,
                'email' => $this->recipient_email,
                'address' => $this->recipient_address,
                'city' => $this->recipient_city,
                'state' => $this->recipient_state,
                'postal_code' => $this->recipient_postal_code,
                'country' => $this->recipient_country,
            ],
            
            // Sender information
            'sender' => [
                'name' => $this->sender_name,
                'phone' => $this->sender_phone,
                'email' => $this->sender_email,
                'address' => $this->sender_address,
                'city' => $this->sender_city,
                'state' => $this->sender_state,
                'postal_code' => $this->sender_postal_code,
                'country' => $this->sender_country,
            ],
            
            // Shipment details
            'delivery_type' => [
                'value' => $this->delivery_type?->value,
                'label' => $this->delivery_type?->label(),
                'expected_days' => $this->delivery_type?->expectedDays(),
            ],
            'payment_method' => $this->payment_method,
            'weight' => $this->weight,
            'dimensions' => $this->dimensions,
            'declared_value' => $this->declared_value,
            'special_instructions' => $this->special_instructions,
            
            // COD information
            'cod' => $this->when($this->isCOD(), [
                'amount' => $this->cod_amount,
                'charge' => $this->cod_charge,
                'collected_amount' => $this->cod_collected_amount,
                'status' => $this->cod_status,
                'collected_at' => $this->cod_collected_at?->format('Y-m-d H:i:s'),
                'settled_at' => $this->cod_settled_at?->format('Y-m-d H:i:s'),
            ]),
            
            // Dates
            'expected_delivery_date' => $this->expected_delivery_date?->format('Y-m-d'),
            'actual_delivery_date' => $this->actual_delivery_date?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Relationships
            'items' => ShipmentItemResource::collection($this->whenLoaded('items')),
            'tracking_events' => TrackingEventResource::collection($this->whenLoaded('trackingEvents')),
            'returns' => ShipmentReturnResource::collection($this->whenLoaded('returns')),
            
            // Computed properties
            'can_be_cancelled' => $this->canBeCancelled(),
            'can_be_returned' => $this->canBeReturned(),
            'is_cod' => $this->isCOD(),
            'is_delivered' => $this->isDelivered(),
        ];
    }
}