<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Calendar\Models\Calendar;
use App\Domain\Calendar\Models\CalendarEvent;
use App\Domain\Calendar\Services\CalendarService;
use App\Domain\Calendar\Services\EventService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Calendar",
 *     description="Calendar and event management"
 * )
 */
class CalendarController extends BaseApiController
{
    public function __construct(
        private CalendarService $calendarService,
        private EventService $eventService
    ) {}

    /**
     * Get all calendars for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $calendars = $this->calendarService->getUserCalendars(
            $request->user(),
            $request->user()->activeCompany()->id
        );

        return $this->success($calendars);
    }

    /**
     * Create a new calendar.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:personal,team,company,resource',
            'color' => 'nullable|string|max:7',
            'is_public' => 'boolean',
            'team_id' => 'nullable|integer',
        ]);

        $calendar = $this->calendarService->createCalendar(array_merge($validated, [
            'company_id' => $request->user()->activeCompany()->id,
            'user_id' => $validated['type'] === 'personal' ? $request->user()->id : null,
        ]));

        return $this->success($calendar, 'Calendar created successfully', 201);
    }

    /**
     * Get a specific calendar.
     */
    public function show(Calendar $calendar): JsonResponse
    {
        return $this->success($calendar->load('events'));
    }

    /**
     * Update a calendar.
     */
    public function update(Request $request, Calendar $calendar): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'is_public' => 'boolean',
        ]);

        $calendar = $this->calendarService->updateCalendar($calendar, $validated);

        return $this->success($calendar, 'Calendar updated successfully');
    }

    /**
     * Delete a calendar.
     */
    public function destroy(Calendar $calendar): JsonResponse
    {
        $this->calendarService->deleteCalendar($calendar);

        return $this->success(null, 'Calendar deleted successfully');
    }

    /**
     * Get events for a calendar.
     */
    public function events(Request $request, Calendar $calendar): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'nullable|string',
            'status' => 'nullable|string',
            'eventable_type' => 'nullable|string',
        ]);

        $events = $this->calendarService->getCalendarEvents(
            $calendar,
            Carbon::parse($validated['start_date']),
            Carbon::parse($validated['end_date']),
            $request->only(['type', 'status', 'eventable_type'])
        );

        return $this->success($events);
    }

    /**
     * Get all events for the authenticated user across all calendars.
     */
    public function userEvents(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'nullable|string',
            'status' => 'nullable|string',
            'eventable_type' => 'nullable|string',
            'calendar_id' => 'nullable|integer',
        ]);

        $events = $this->calendarService->getUserEvents(
            $request->user(),
            $request->user()->activeCompany()->id,
            Carbon::parse($validated['start_date']),
            Carbon::parse($validated['end_date']),
            $request->only(['type', 'status', 'eventable_type', 'calendar_id'])
        );

        return $this->success($events);
    }

    /**
     * Get upcoming events.
     */
    public function upcoming(Request $request, Calendar $calendar): JsonResponse
    {
        $limit = $request->integer('limit', 10);
        $events = $this->calendarService->getUpcomingEvents($calendar, $limit);

        return $this->success($events);
    }

    /**
     * Get calendar statistics.
     */
    public function statistics(Request $request, Calendar $calendar): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $statistics = $this->calendarService->getCalendarStatistics(
            $calendar,
            Carbon::parse($validated['start_date']),
            Carbon::parse($validated['end_date'])
        );

        return $this->success($statistics);
    }

    /**
     * Create a new event.
     */
    public function storeEvent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'calendar_id' => 'required|exists:calendars,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after:start_at',
            'all_day' => 'boolean',
            'type' => 'required|in:meeting,call,task,deadline,leave,availability,milestone,personal,company',
            'color' => 'nullable|string|max:7',
            'reminder_minutes' => 'nullable|integer|min:0',
            'attendees' => 'nullable|array',
        ]);

        $event = $this->eventService->createEvent(array_merge($validated, [
            'company_id' => $request->user()->activeCompany()->id,
            'user_id' => $request->user()->id,
            'status' => CalendarEvent::STATUS_SCHEDULED,
        ]));

        return $this->success($event, 'Event created successfully', 201);
    }

    /**
     * Update an event.
     */
    public function updateEvent(Request $request, CalendarEvent $event): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'start_at' => 'sometimes|date',
            'end_at' => 'nullable|date|after:start_at',
            'all_day' => 'boolean',
            'type' => 'sometimes|in:meeting,call,task,deadline,leave,availability,milestone,personal,company',
            'status' => 'sometimes|in:scheduled,in_progress,completed,cancelled',
            'color' => 'nullable|string|max:7',
            'reminder_minutes' => 'nullable|integer|min:0',
            'attendees' => 'nullable|array',
        ]);

        $event = $this->eventService->updateEvent($event, $validated);

        return $this->success($event, 'Event updated successfully');
    }

    /**
     * Delete an event.
     */
    public function destroyEvent(CalendarEvent $event): JsonResponse
    {
        $this->eventService->deleteEvent($event);

        return $this->success(null, 'Event deleted successfully');
    }

    /**
     * Complete an event.
     */
    public function completeEvent(CalendarEvent $event): JsonResponse
    {
        $event = $this->eventService->completeEvent($event);

        return $this->success($event, 'Event marked as completed');
    }

    /**
     * Cancel an event.
     */
    public function cancelEvent(CalendarEvent $event): JsonResponse
    {
        $event = $this->eventService->cancelEvent($event);

        return $this->success($event, 'Event cancelled');
    }

    /**
     * Reschedule an event.
     */
    public function rescheduleEvent(Request $request, CalendarEvent $event): JsonResponse
    {
        $validated = $request->validate([
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after:start_at',
        ]);

        $event = $this->eventService->rescheduleEvent(
            $event,
            Carbon::parse($validated['start_at']),
            isset($validated['end_at']) ? Carbon::parse($validated['end_at']) : null
        );

        return $this->success($event, 'Event rescheduled successfully');
    }

    /**
     * Check for conflicts.
     */
    public function checkConflicts(Request $request, Calendar $calendar): JsonResponse
    {
        $validated = $request->validate([
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'exclude_event_id' => 'nullable|integer',
        ]);

        $conflicts = $this->calendarService->getConflictingEvents(
            $calendar,
            Carbon::parse($validated['start_at']),
            Carbon::parse($validated['end_at']),
            $validated['exclude_event_id'] ?? null
        );

        return $this->success([
            'has_conflicts' => $conflicts->isNotEmpty(),
            'conflicts' => $conflicts,
        ]);
    }
}
