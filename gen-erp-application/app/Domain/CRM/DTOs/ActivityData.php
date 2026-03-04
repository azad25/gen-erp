<?php

namespace App\Domain\CRM\DTOs;

use App\Domain\CRM\Enums\ActivityType;

class ActivityData
{
    public function __construct(
        public readonly ActivityType $type,
        public readonly string $title,
        public readonly string $subjectType,
        public readonly int $subjectId,
        public readonly ?string $description = null,
        public readonly string $status = 'scheduled',
        public readonly string $priority = 'medium',
        public readonly ?\DateTime $scheduledAt = null,
        public readonly ?\DateTime $dueDate = null,
        public readonly ?int $plannedDurationMinutes = null,
        public readonly ?string $direction = null,
        public readonly bool $isReminder = false,
        public readonly ?\DateTime $reminderAt = null,
        public readonly ?string $emailSubject = null,
        public readonly ?string $emailBody = null,
        public readonly ?array $attachments = null,
        public readonly ?string $meetingLocation = null,
        public readonly ?string $meetingLink = null,
        public readonly ?array $attendees = null,
        public readonly ?array $customFields = null,
        public readonly ?array $metadata = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: ActivityType::from($data['type']),
            title: $data['title'],
            subjectType: $data['subject_type'],
            subjectId: (int) $data['subject_id'],
            description: $data['description'] ?? null,
            status: $data['status'] ?? 'scheduled',
            priority: $data['priority'] ?? 'medium',
            scheduledAt: isset($data['scheduled_at']) ? new \DateTime($data['scheduled_at']) : null,
            dueDate: isset($data['due_date']) ? new \DateTime($data['due_date']) : null,
            plannedDurationMinutes: isset($data['planned_duration_minutes']) ? (int) $data['planned_duration_minutes'] : null,
            direction: $data['direction'] ?? null,
            isReminder: (bool) ($data['is_reminder'] ?? false),
            reminderAt: isset($data['reminder_at']) ? new \DateTime($data['reminder_at']) : null,
            emailSubject: $data['email_subject'] ?? null,
            emailBody: $data['email_body'] ?? null,
            attachments: $data['attachments'] ?? null,
            meetingLocation: $data['meeting_location'] ?? null,
            meetingLink: $data['meeting_link'] ?? null,
            attendees: $data['attendees'] ?? null,
            customFields: $data['custom_fields'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'title' => $this->title,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'scheduled_at' => $this->scheduledAt?->format('Y-m-d H:i:s'),
            'due_date' => $this->dueDate?->format('Y-m-d H:i:s'),
            'planned_duration_minutes' => $this->plannedDurationMinutes,
            'direction' => $this->direction,
            'is_reminder' => $this->isReminder,
            'reminder_at' => $this->reminderAt?->format('Y-m-d H:i:s'),
            'email_subject' => $this->emailSubject,
            'email_body' => $this->emailBody,
            'attachments' => $this->attachments,
            'meeting_location' => $this->meetingLocation,
            'meeting_link' => $this->meetingLink,
            'attendees' => $this->attendees,
            'custom_fields' => $this->customFields,
            'metadata' => $this->metadata,
        ];
    }
}