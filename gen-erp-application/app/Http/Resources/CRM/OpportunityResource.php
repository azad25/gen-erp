<?php

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'probability' => $this->probability,
            'weighted_value' => $this->weighted_value,
            'total_amount' => $this->total_amount,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'expected_close_date' => $this->expected_close_date?->format('Y-m-d'),
            'actual_close_date' => $this->actual_close_date?->format('Y-m-d'),
            'status' => [
                'value' => $this->status,
                'label' => __('crm.status.' . $this->status),
                'is_open' => $this->is_open,
                'is_won' => $this->is_won,
                'is_lost' => $this->is_lost,
                'is_closed' => $this->is_closed,
            ],
            'close_reason' => $this->close_reason,
            'stage_order' => $this->stage_order,
            'source' => $this->source,
            'campaign' => $this->campaign,
            'products' => $this->products,
            'last_activity_at' => $this->last_activity_at?->format('Y-m-d H:i:s'),
            'won_at' => $this->won_at?->format('Y-m-d H:i:s'),
            'lost_at' => $this->lost_at?->format('Y-m-d H:i:s'),
            'days_in_stage' => $this->days_in_stage,
            'custom_fields' => $this->custom_fields,
            'notes' => $this->notes,
            'pipeline' => $this->whenLoaded('pipeline', function () {
                return [
                    'id' => $this->pipeline->id,
                    'uuid' => $this->pipeline->uuid,
                    'name' => $this->pipeline->name,
                    'color' => $this->pipeline->color,
                    'is_default' => $this->pipeline->is_default,
                ];
            }, [
                'id' => $this->pipeline_id,
                'name' => null,
            ]),
            'stage' => $this->whenLoaded('stage', function () {
                return [
                    'id' => $this->stage->id,
                    'uuid' => $this->stage->uuid,
                    'name' => $this->stage->name,
                    'color' => $this->stage->color,
                    'probability' => $this->stage->probability,
                    'sort_order' => $this->stage->sort_order,
                    'is_closed_won' => $this->stage->is_closed_won,
                    'is_closed_lost' => $this->stage->is_closed_lost,
                ];
            }, [
                'id' => $this->stage_id,
                'name' => null,
            ]),
            'lead' => $this->whenLoaded('lead', function () {
                return [
                    'id' => $this->lead->id,
                    'uuid' => $this->lead->uuid,
                    'full_name' => $this->lead->full_name,
                    'email' => $this->lead->email,
                    'phone' => $this->lead->phone,
                    'company_name' => $this->lead->company_name,
                ];
            }, $this->lead_id ? ['id' => $this->lead_id] : null),
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'email' => $this->customer->email,
                    'phone' => $this->customer->phone,
                ];
            }, $this->customer_id ? ['id' => $this->customer_id] : null),
            'assigned_to' => $this->whenLoaded('assignedTo', function () {
                return [
                    'id' => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                    'email' => $this->assignedTo->email,
                ];
            }, $this->assigned_to ? ['id' => $this->assigned_to] : null),
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ];
            }, [
                'id' => $this->created_by,
                'name' => null,
            ]),
            'activities_count' => $this->whenCounted('activities'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}