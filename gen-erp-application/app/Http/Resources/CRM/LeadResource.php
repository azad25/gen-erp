<?php

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company_name' => $this->company_name,
            'job_title' => $this->job_title,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'source' => $this->source ? [
                'value' => $this->source->value,
                'label' => $this->source->label(),
                'icon' => $this->source->icon(),
            ] : null,
            'score' => $this->score,
            'estimated_value' => $this->estimated_value,
            'currency' => $this->currency,
            'expected_close_date' => $this->expected_close_date?->format('Y-m-d'),
            'last_contacted_at' => $this->last_contacted_at?->format('Y-m-d H:i:s'),
            'qualified_at' => $this->qualified_at?->format('Y-m-d H:i:s'),
            'converted_at' => $this->converted_at?->format('Y-m-d H:i:s'),
            'is_qualified' => $this->is_qualified,
            'is_converted' => $this->is_converted,
            'custom_fields' => $this->custom_fields,
            'notes' => $this->notes,
            'assigned_to' => $this->whenLoaded('assignedTo', function () {
                return [
                    'id' => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                    'email' => $this->assignedTo->email,
                ];
            }),
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ];
            }),
            'converted_to_customer' => $this->whenLoaded('convertedToCustomer', function () {
                return [
                    'id' => $this->convertedToCustomer->id,
                    'name' => $this->convertedToCustomer->name,
                    'email' => $this->convertedToCustomer->email,
                ];
            }),
            'tags' => $this->whenLoaded('tags', function () {
                return $this->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'color' => $tag->color,
                        'tagged_at' => $tag->pivot->tagged_at,
                        'tagged_by' => $tag->pivot->tagged_by,
                    ];
                });
            }),
            'notes_count' => $this->whenCounted('notes'),
            'activities_count' => $this->whenCounted('activities'),
            'opportunities_count' => $this->whenCounted('opportunities'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}