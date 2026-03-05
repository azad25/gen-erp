<?php

namespace Tests\Unit\Domain\Calendar;

use App\Domain\Calendar\Models\Calendar;
use App\Domain\Calendar\Models\CalendarEvent;
use App\Domain\Calendar\Services\CalendarService;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    private CalendarService $calendarService;
    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->calendarService = app(CalendarService::class);
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, [
            'role' => 'admin',
            'is_owner' => true,
            'is_active' => true,
        ]);
    }

    public function test_can_create_calendar()
    {
        $data = [
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'name' => 'My Calendar',
            'description' => 'Personal calendar',
            'type' => 'personal',
            'color' => '#3B82F6',
            'is_default' => true,
        ];

        $calendar = $this->calendarService->createCalendar($data);

        $this->assertInstanceOf(Calendar::class, $calendar);
        $this->assertEquals('My Calendar', $calendar->name);
        $this->assertEquals('personal', $calendar->type);
        $this->assertTrue($calendar->is_default);
    }

    public function test_can_update_calendar()
    {
        $calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'name' => 'Old Name',
        ]);

        $updated = $this->calendarService->updateCalendar($calendar, [
            'name' => 'New Name',
            'description' => 'Updated description',
        ]);

        $this->assertEquals('New Name', $updated->name);
        $this->assertEquals('Updated description', $updated->description);
    }

    public function test_can_delete_calendar()
    {
        $calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $this->calendarService->deleteCalendar($calendar);

        $this->assertSoftDeleted($calendar);
    }

    public function test_can_get_user_calendars()
    {
        Calendar::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $calendars = $this->calendarService->getUserCalendars($this->user, $this->company->id);

        $this->assertCount(3, $calendars);
    }

    public function test_can_get_or_create_user_calendar()
    {
        $calendar = $this->calendarService->getOrCreateUserCalendar($this->user, $this->company->id);

        $this->assertInstanceOf(Calendar::class, $calendar);
        $this->assertEquals($this->user->id, $calendar->user_id);
        $this->assertTrue($calendar->is_default);

        // Should return same calendar on second call
        $sameCalendar = $this->calendarService->getOrCreateUserCalendar($this->user, $this->company->id);
        $this->assertEquals($calendar->id, $sameCalendar->id);
    }

    public function test_can_get_calendar_events()
    {
        $calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        CalendarEvent::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'calendar_id' => $calendar->id,
            'user_id' => $this->user->id,
            'start_at' => Carbon::now()->addDays(1),
        ]);

        $events = $this->calendarService->getCalendarEvents(
            $calendar,
            Carbon::now(),
            Carbon::now()->addDays(7)
        );

        $this->assertCount(5, $events);
    }

    public function test_can_get_user_events()
    {
        $calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        CalendarEvent::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'calendar_id' => $calendar->id,
            'user_id' => $this->user->id,
            'start_at' => Carbon::now()->addDays(1),
        ]);

        $events = $this->calendarService->getUserEvents(
            $this->user,
            $this->company->id,
            Carbon::now(),
            Carbon::now()->addDays(7)
        );

        $this->assertCount(3, $events);
    }

    public function test_can_detect_conflicts()
    {
        $calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $calendar->id,
            'user_id' => $this->user->id,
            'start_at' => Carbon::now()->addHours(1),
            'end_at' => Carbon::now()->addHours(2),
        ]);

        $hasConflicts = $this->calendarService->hasConflicts(
            $calendar,
            Carbon::now()->addHours(1)->addMinutes(30),
            Carbon::now()->addHours(2)->addMinutes(30)
        );

        $this->assertTrue($hasConflicts);
    }

    public function test_can_get_conflicting_events()
    {
        $calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $calendar->id,
            'user_id' => $this->user->id,
            'start_at' => Carbon::now()->addHours(1),
            'end_at' => Carbon::now()->addHours(2),
        ]);

        $conflicts = $this->calendarService->getConflictingEvents(
            $calendar,
            Carbon::now()->addHours(1)->addMinutes(30),
            Carbon::now()->addHours(2)->addMinutes(30)
        );

        $this->assertCount(1, $conflicts);
    }

    public function test_can_get_calendar_statistics()
    {
        $calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $calendar->id,
            'user_id' => $this->user->id,
            'type' => 'meeting',
            'status' => 'scheduled',
            'start_at' => Carbon::now()->addDays(1),
        ]);

        CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $calendar->id,
            'user_id' => $this->user->id,
            'type' => 'task',
            'status' => 'completed',
            'start_at' => Carbon::now()->addDays(2),
        ]);

        $stats = $this->calendarService->getCalendarStatistics(
            $calendar,
            Carbon::now(),
            Carbon::now()->addDays(7)
        );

        $this->assertEquals(2, $stats['total_events']);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertArrayHasKey('by_status', $stats);
    }

    public function test_can_get_upcoming_events()
    {
        $calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        CalendarEvent::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'calendar_id' => $calendar->id,
            'user_id' => $this->user->id,
            'start_at' => Carbon::now()->addDays(rand(1, 10)),
        ]);

        $upcoming = $this->calendarService->getUpcomingEvents($calendar, 3);

        $this->assertCount(3, $upcoming);
    }
}
