<?php

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PipelineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'settings' => $this->settings,
            'auto_move_stages' => $this->auto_move_stages,
            'default_probability' => $this->default_probability,
            'opportunities_count' => $this->opportunities_count,
            'total_value' => $this->total_value,
            'won_value' => $this->won_value,
            'lost_value' => $this->lost_value,
            'conversion_rate' => $this->conversion_rate,
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
            'stages' => $this->whenLoaded('stages', function () {
                return PipelineStageResource::collection($this->stages);
            }, []),
            'stages_count' => $this->whenCounted('stages'),
            'opportunities' => $this->whenLoaded('opportunities', function () {
                return OpportunityResource::collection($this->opportunities);
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}