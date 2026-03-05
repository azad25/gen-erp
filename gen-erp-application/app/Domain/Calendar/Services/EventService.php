<?php

namespace App\Domain\Calendar\Services;

use App\Domain\Calendar\Models\Calendar;
use App\Domain\Calendar\Models\CalendarEvent;
use Carbon\Carbon;

class EventService
{
    /**
     * Create a new calendar event.
     */
    public function createEvent(array $data): CalendarEvent
    {
        return CalendarEvent::create($data);
    }

    /**
     * Create event from another model (Activity, Task, etc.).
     */
    public function createEventFromModel(
        $model,
        Calendar $calendar,
        string $title,
        Carbon $startAt,
        ?Carbon $endAt = null,
        string $type = CalendarEvent::TYPE_PERSONAL,
        ?array $additionalData = []
    ): CalendarEvent {
        return CalendarEvent::create(array_merge([
            'company_id' => $calendar->company_id,
            'calendar_id' => $calendar->id,
            'user_id' => $calendar->user_id,
            'eventable_type' => get_class($model),
            'eventable_id' => $model->id,
            'title' => $title,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'type' => $type,
            'status' => CalendarEvent::STATUS_SCHEDULED,
            'color' => $this->getColorForType($type),
        ], $additionalData));
    }

    /**
     * Update a calendar event.
     */
    public function updateEvent(CalendarEvent $event, array $data): CalendarEvent
    {
        $event->update($data);
        return $event->fresh();
    }

    /**
     * Delete a calendar event.
     */
    public function deleteEvent(CalendarEvent $event): bool
    {
        return $event->delete();
    }

    /**
     * Mark event as completed.
     */
    public function completeEvent(CalendarEvent $event): CalendarEvent
    {
        $event->update([
            'status' => CalendarEvent::STATUS_COMPLETED,
        ]);

        return $event->fresh();
    }

    /**
     * Cancel an event.
     */
    public function cancelEvent(CalendarEvent $event): CalendarEvent
    {
        $event->update([
            'status' => CalendarEvent::STATUS_CANCELLED,
        ]);

        return $event->fresh();
    }

    /**
     * Reschedule an event.
     */
    public function rescheduleEvent(
        CalendarEvent $event,
        Carbon $newStartAt,
        ?Carbon $newEndAt = null
    ): CalendarEvent {
        $event->update([
            'start_at' => $newStartAt,
            'end_at' => $newEndAt ?? $event->end_at,
        ]);

        return $event->fresh();
    }

    /**
     * Add attendees to an event.
     */
    public function addAttendees(CalendarEvent $event, array $attendees): CalendarEvent
    {
        $currentAttendees = $event->attendees ?? [];
        $event->update([
            'attendees' => array_merge($currentAttendees, $attendees),
        ]);

        return $event->fresh();
    }

    /**
     * Remove attendees from an event.
     */
    public function removeAttendees(CalendarEvent $event, array $attendeeIds): CalendarEvent
    {
        $currentAttendees = $event->attendees ?? [];
        $event->update([
            'attendees' => array_values(array_filter($currentAttendees, function ($attendee) use ($attendeeIds) {
                return !in_array($attendee['id'] ?? null, $attendeeIds);
            })),
        ]);

        return $event->fresh();
    }

    /**
     * Get default color for event type.
     */
    public function getColorForType(string $type): string
    {
        return match ($type) {
            CalendarEvent::TYPE_MEETING => '#3B82F6',      // Blue
            CalendarEvent::TYPE_CALL => '#10B981',         // Green
            CalendarEvent::TYPE_TASK => '#F59E0B',         // Amber
            CalendarEvent::TYPE_DEADLINE => '#EF4444',     // Red
            CalendarEvent::TYPE_LEAVE => '#8B5CF6',        // Purple
            CalendarEvent::TYPE_AVAILABILITY => '#06B6D4', // Cyan
            CalendarEvent::TYPE_MILESTONE => '#EC4899',    // Pink
            CalendarEvent::TYPE_COMPANY => '#6366F1',      // Indigo
            default => '#6B7280',                          // Gray
        };
    }
}
