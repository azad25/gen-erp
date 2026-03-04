<?php

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

class CreateActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:call,email,meeting,task,note,sms,follow_up,demo,proposal_sent,contract_sent,payment_received,complaint,support',
            'title' => 'required|string|max:255',
            'subject_type' => 'required|string|max:255',
            'subject_id' => 'required|integer',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:scheduled,in_progress,completed,cancelled',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'scheduled_at' => 'nullable|date|after:now',
            'due_date' => 'nullable|date',
            'planned_duration_minutes' => 'nullable|integer|min:1',
            'direction' => 'nullable|string|in:inbound,outbound',
            'is_reminder' => 'boolean',
            'reminder_at' => 'nullable|date|after:now',
            'email_subject' => 'nullable|string|max:255',
            'email_body' => 'nullable|string',
            'attachments' => 'nullable|array',
            'meeting_location' => 'nullable|string|max:255',
            'meeting_link' => 'nullable|url',
            'attendees' => 'nullable|array',
            'custom_fields' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => __('crm.validation.activity_type_required'),
            'title.required' => __('crm.validation.title_required'),
            'subject_type.required' => __('crm.validation.subject_required'),
            'subject_id.required' => __('crm.validation.subject_required'),
            'scheduled_at.after' => 'Scheduled time must be in the future',
            'due_date.after' => 'Due date must be in the future',
            'reminder_at.after' => 'Reminder time must be in the future',
            'meeting_link.url' => 'Meeting link must be a valid URL',
        ];
    }
}