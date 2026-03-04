<?php

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PipelineStageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'probability' => $this->probability,
            'is_closed_won' => $this->is_closed_won,
            'is_closed_lost' => $this->is_closed_lost,
            'is_closed' => $this->is_closed,
            'requires_reason' => $this->requires_reason,
            'entry_actions' => $this->entry_actions,
            'exit_actions' => $this->exit_actions,
            'max_days_in_stage' => $this->max_days_in_stage,
            'opportunities_count' => $this->opportunities_count,
            'total_value' => $this->total_value,
            'average_days' => $this->average_days,
            'conversion_rate' => $this->conversion_rate,
            'pipeline' => $this->whenLoaded('pipeline', function () {
                return [
                    'id' => $this->pipeline->id,
                    'uuid' => $this->pipeline->uuid,
                    'name' => $this->pipeline->name,
                    'color' => $this->pipeline->color,
                ];
            }),
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ];
            }),
            'opportunities' => $this->whenLoaded('opportunities', function () {
                return OpportunityResource::collection($this->opportunities);
            }),
            'next_stage' => $this->next_stage ? [
                'id' => $this->next_stage->id,
                'name' => $this->next_stage->name,
                'sort_order' => $this->next_stage->sort_order,
            ] : null,
            'previous_stage' => $this->previous_stage ? [
                'id' => $this->previous_stage->id,
                'name' => $this->previous_stage->name,
                'sort_order' => $this->previous_stage->sort_order,
            ] : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}