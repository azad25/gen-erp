<?php

namespace App\Domain\Logistics\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'location' => $this->location,
            'description' => $this->description,
            'event_time' => $this->event_time instanceof \Carbon\Carbon 
                ? $this->event_time->format('Y-m-d H:i:s') 
                : $this->event_time,
            'carrier_data' => $this->carrier_data,
            'created_at' => $this->created_at instanceof \Carbon\Carbon 
                ? $this->created_at->format('Y-m-d H:i:s') 
                : $this->created_at,
        ];
    }
}