<?php

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'type' => [
                'value' => $this->type->value,
                'label' => $this->type->label(),
                'icon' => $this->type->icon(),
                'color' => $this->type->color(),
                'requires_outcome' => $this->type->requiresOutcome(),
                'is_schedulable' => $this->type->isSchedulable(),
            ],
            'title' => $this->title,
            'description' => $this->description,
            'status' => [
                'value' => $this->status,
                'label' => __('crm.status.' . $this->status),
            ],
            'priority' => [
                'value' => $this->priority,
                'label' => __('crm.priority.' . $this->priority),
            ],
            'scheduled_at' => $this->scheduled_at?->format('Y-m-d H:i:s'),
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'due_date' => $this->due_date?->format('Y-m-d H:i:s'),
            'duration_minutes' => $this->duration_minutes,
            'planned_duration_minutes' => $this->planned_duration_minutes,
            'actual_duration' => $this->actual_duration,
            'direction' => $this->direction,
            'outcome' => $this->outcome,
            'outcome_notes' => $this->outcome_notes,
            'is_reminder' => $this->is_reminder,
            'reminder_at' => $this->reminder_at?->format('Y-m-d H:i:s'),
            'reminder_sent' => $this->reminder_sent,
            'is_completed' => $this->is_completed,
            'is_overdue' => $this->is_overdue,
            'email_subject' => $this->email_subject,
            'email_body' => $this->email_body,
            'attachments' => $this->attachments,
            'meeting_location' => $this->meeting_location,
            'meeting_link' => $this->meeting_link,
            'attendees' => $this->attendees,
            'custom_fields' => $this->custom_fields,
            'metadata' => $this->metadata,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'subject' => $this->whenLoaded('subject', function () {
                // Return different data based on subject type
                switch ($this->subject_type) {
                    case 'App\Domain\CRM\Models\Lead':
                        return [
                            'type' => 'lead',
                            'id' => $this->subject->id,
                            'uuid' => $this->subject->uuid,
                            'name' => $this->subject->full_name,
                            'email' => $this->subject->email,
                            'phone' => $this->subject->phone,
                            'company_name' => $this->subject->company_name,
                        ];
                    case 'App\Domain\CRM\Models\Opportunity':
                        return [
                            'type' => 'opportunity',
                            'id' => $this->subject->id,
                            'uuid' => $this->subject->uuid,
                            'name' => $this->subject->name,
                            'amount' => $this->subject->amount,
                            'status' => $this->subject->status,
                        ];
                    case 'App\Models\Customer':
                        return [
                            'type' => 'customer',
                            'id' => $this->subject->id,
                            'name' => $this->subject->name,
                            'email' => $this->subject->email,
                            'phone' => $this->subject->phone,
                        ];
                    default:
                        return [
                            'type' => 'unknown',
                            'id' => $this->subject->id,
                        ];
                }
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}