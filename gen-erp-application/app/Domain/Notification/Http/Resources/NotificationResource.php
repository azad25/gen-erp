<?php

namespace App\Domain\Notification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'type' => $this->type,
            'title_key' => $this->title_key,
            'body_key' => $this->body_key,
            'translation_params' => $this->translation_params,
            'icon' => $this->icon,
            'color' => $this->color,
            'action_url' => $this->action_url,
            'action_label_key' => $this->action_label_key,
            'meta' => $this->meta,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}