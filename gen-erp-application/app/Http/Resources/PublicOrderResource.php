<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public order API resource.
 */
class PublicOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'customer_id' => $this->customer_id,
            'order_number' => $this->order_number,
            'customer' => [
                'email' => $this->customer_email,
                'first_name' => $this->customer_first_name,
                'last_name' => $this->customer_last_name,
                'full_name' => $this->getCustomerFullName(),
                'phone' => $this->customer_phone,
            ],
            'billing_address' => [
                'line_1' => $this->billing_address_line_1,
                'line_2' => $this->billing_address_line_2,
                'city' => $this->billing_city,
                'state' => $this->billing_state,
                'postal_code' => $this->billing_postal_code,
                'country' => $this->billing_country,
                'formatted' => $this->getBillingAddress(),
            ],
            'shipping_address' => [
                'line_1' => $this->shipping_address_line_1,
                'line_2' => $this->shipping_address_line_2,
                'city' => $this->shipping_city,
                'state' => $this->shipping_state,
                'postal_code' => $this->shipping_postal_code,
                'country' => $this->shipping_country,
                'formatted' => $this->getShippingAddress(),
            ],
            'totals' => [
                'subtotal' => $this->subtotal,
                'shipping_cost' => $this->shipping_cost,
                'tax_amount' => $this->tax_amount,
                'discount_amount' => $this->discount_amount,
                'total_amount' => $this->total_amount,
            ],
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
                'icon' => $this->status->icon(),
            ],
            'payment' => [
                'status' => [
                    'value' => $this->payment_status->value,
                    'label' => $this->payment_status->label(),
                    'color' => $this->payment_status->color(),
                    'icon' => $this->payment_status->icon(),
                ],
                'method' => [
                    'value' => $this->payment_method->value,
                    'label' => $this->payment_method->label(),
                    'description' => $this->payment_method->description(),
                    'icon' => $this->payment_method->icon(),
                ],
            ],
            'notes' => [
                'customer' => $this->customer_notes,
                'admin' => $this->admin_notes,
            ],
            'tracking_number' => $this->tracking_number,
            'item_count' => $this->getItemCount(),
            'items' => PublicOrderItemResource::collection($this->whenLoaded('items')),
            'timestamps' => [
                'placed_at' => $this->placed_at->toISOString(),
                'completed_at' => $this->completed_at?->toISOString(),
                'cancelled_at' => $this->cancelled_at?->toISOString(),
                'created_at' => $this->created_at->toISOString(),
                'updated_at' => $this->updated_at->toISOString(),
            ],
        ];
    }
}