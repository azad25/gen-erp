<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\CMS\Models\ContactSubmission
 */
class ContactSubmissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'subject' => $this->subject,
            'message' => $this->message,
            'form_data' => $this->form_data,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'source' => $this->source,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'contacted_at' => $this->contacted_at?->toISOString(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'site' => $this->whenLoaded('site', function () {
                return [
                    'id' => $this->site->id,
                    'name' => $this->site->name,
                    'subdomain' => $this->site->subdomain,
                ];
            }),
            'assigned_user' => $this->whenLoaded('assignedUser', function () {
                return $this->assignedUser ? [
                    'id' => $this->assignedUser->id,
                    'name' => $this->assignedUser->name,
                    'email' => $this->assignedUser->email,
                ] : null;
            }),
        ];
    }
}