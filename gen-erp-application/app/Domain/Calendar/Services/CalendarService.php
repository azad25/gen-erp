<?php

namespace App\Domain\Calendar\Services;

use App\Domain\Calendar\Models\Calendar;
use App\Domain\Calendar\Models\CalendarEvent;
use App\Domain\Auth\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarService
{
    /**
     * Create a new calendar.
     */
    public function createCalendar(array $data): Calendar
    {
        return Calendar::create($data);
    }

    /**
     * Update a calendar.
     */
    public function updateCalendar(Calendar $calendar, array $data): Calendar
    {
        $calendar->update($data);
        return $calendar->fresh();
    }

    /**
     * Delete a calendar.
     */
    public function deleteCalendar(Calendar $calendar): bool
    {
        return $calendar->delete();
    }

    /**
     * Get or create user's default calendar.
     */
    public function getOrCreateUserCalendar(User $user, int $companyId): Calendar
    {
        return Calendar::firstOrCreate(
            [
                'user_id' => $user->id,
                'company_id' => $companyId,
                'type' => Calendar::TYPE_PERSONAL,
                'is_default' => true,
            ],
            [
                'name' => $user->name . "'s Calendar",
                'description' => 'Personal calendar',
                'color' => '#3B82F6',
                'is_public' => false,
                'timezone' => config('app.timezone'),
            ]
        );
    }

    /**
     * Get all calendars for a user.
     */
    public function getUserCalendars(User $user, int $companyId): Collection
    {
        return Calendar::where('company_id', $companyId)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('is_public', true)
                    ->orWhere('type', Calendar::TYPE_COMPANY);
            })
            ->get();
    }

    /**
     * Get events for a calendar in a date range.
     */
    public function getCalendarEvents(
        Calendar $calendar,
        Carbon $startDate,
        Carbon $endDate,
        ?array $filters = []
    ): Collection {
        $query = $calendar->events()
            ->with('eventable')
            ->inRange($startDate, $endDate);

        // Apply filters
        if (!empty($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        if (!empty($filters['eventable_type'])) {
            $query->where('eventable_type', $filters['eventable_type']);
        }

        return $query->orderBy('start_at')->get();
    }

    /**
     * Get all events for a user across all their calendars.
     */
    public function getUserEvents(
        User $user,
        int $companyId,
        Carbon $startDate,
        Carbon $endDate,
        ?array $filters = []
    ): Collection {
        $calendars = $this->getUserCalendars($user, $companyId);
        $calendarIds = $calendars->pluck('id');

        $query = CalendarEvent::whereIn('calendar_id', $calendarIds)
            ->with(['eventable', 'calendar'])
            ->inRange($startDate, $endDate);

        // Apply filters
        if (!empty($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        if (!empty($filters['eventable_type'])) {
            $query->where('eventable_type', $filters['eventable_type']);
        }

        if (!empty($filters['calendar_id'])) {
            $query->where('calendar_id', $filters['calendar_id']);
        }

        return $query->orderBy('start_at')->get();
    }

    /**
     * Get events for a specific date.
     */
    public function getEventsForDate(Calendar $calendar, Carbon $date): Collection
    {
        return $calendar->events()
            ->with('eventable')
            ->onDate($date)
            ->orderBy('start_at')
            ->get();
    }

    /**
     * Get upcoming events.
     */
    public function getUpcomingEvents(Calendar $calendar, int $limit = 10): Collection
    {
        return $calendar->events()
            ->with('eventable')
            ->upcoming()
            ->limit($limit)
            ->get();
    }

    /**
     * Check for event conflicts.
     */
    public function hasConflicts(
        Calendar $calendar,
        Carbon $startAt,
        Carbon $endAt,
        ?int $excludeEventId = null
    ): bool {
        $query = $calendar->events()
            ->inRange($startAt, $endAt)
            ->where('status', '!=', CalendarEvent::STATUS_CANCELLED);

        if ($excludeEventId) {
            $query->where('id', '!=', $excludeEventId);
        }

        return $query->exists();
    }

    /**
     * Get conflicting events.
     */
    public function getConflictingEvents(
        Calendar $calendar,
        Carbon $startAt,
        Carbon $endAt,
        ?int $excludeEventId = null
    ): Collection {
        $query = $calendar->events()
            ->with('eventable')
            ->inRange($startAt, $endAt)
            ->where('status', '!=', CalendarEvent::STATUS_CANCELLED);

        if ($excludeEventId) {
            $query->where('id', '!=', $excludeEventId);
        }

        return $query->get();
    }

    /**
     * Get calendar statistics.
     */
    public function getCalendarStatistics(Calendar $calendar, Carbon $startDate, Carbon $endDate): array
    {
        $events = $calendar->events()
            ->inRange($startDate, $endDate)
            ->get();

        return [
            'total_events' => $events->count(),
            'by_type' => $events->groupBy('type')->map->count(),
            'by_status' => $events->groupBy('status')->map->count(),
            'completed' => $events->where('status', CalendarEvent::STATUS_COMPLETED)->count(),
            'upcoming' => $events->where('start_at', '>=', now())->count(),
            'overdue' => $events->where('end_at', '<', now())
                ->where('status', '!=', CalendarEvent::STATUS_COMPLETED)
                ->count(),
        ];
    }
}
