<?php

namespace Tests\Unit\Domain\Calendar;

use App\Domain\Calendar\Models\Calendar;
use App\Domain\Calendar\Models\CalendarEvent;
use App\Domain\Calendar\Services\EventService;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventServiceTest extends TestCase
{
    use RefreshDatabase;

    private EventService $eventService;
    private User $user;
    private Company $company;
    private Calendar $calendar;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->eventService = app(EventService::class);
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, [
            'role' => 'admin',
            'is_owner' => true,
            'is_active' => true,
        ]);
        
        $this->calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_create_event()
    {
        $data = [
            'company_id' => $this->company->id,
            'calendar_id' => $this->calendar->id,
            'user_id' => $this->user->id,
            'title' => 'Team Meeting',
            'description' => 'Weekly sync',
            'start_at' => Carbon::now()->addDays(1),
            'end_at' => Carbon::now()->addDays(1)->addHour(),
            'type' => 'meeting',
            'status' => 'scheduled',
        ];

        $event = $this->eventService->createEvent($data);

        $this->assertInstanceOf(CalendarEvent::class, $event);
        $this->assertEquals('Team Meeting', $event->title);
        $this->assertEquals('meeting', $event->type);
        $this->assertEquals('scheduled', $event->status);
    }

    public function test_can_update_event()
    {
        $event = CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $this->calendar->id,
            'user_id' => $this->user->id,
            'title' => 'Old Title',
        ]);

        $updated = $this->eventService->updateEvent($event, [
            'title' => 'New Title',
            'description' => 'Updated description',
        ]);

        $this->assertEquals('New Title', $updated->title);
        $this->assertEquals('Updated description', $updated->description);
    }

    public function test_can_delete_event()
    {
        $event = CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $this->calendar->id,
            'user_id' => $this->user->id,
        ]);

        $this->eventService->deleteEvent($event);

        $this->assertSoftDeleted($event);
    }

    public function test_can_complete_event()
    {
        $event = CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $this->calendar->id,
            'user_id' => $this->user->id,
            'status' => 'scheduled',
        ]);

        $completed = $this->eventService->completeEvent($event);

        $this->assertEquals('completed', $completed->status);
    }

    public function test_can_cancel_event()
    {
        $event = CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $this->calendar->id,
            'user_id' => $this->user->id,
            'status' => 'scheduled',
        ]);

        $cancelled = $this->eventService->cancelEvent($event);

        $this->assertEquals('cancelled', $cancelled->status);
    }

    public function test_can_reschedule_event()
    {
        $event = CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $this->calendar->id,
            'user_id' => $this->user->id,
            'start_at' => Carbon::now()->addDays(1),
            'end_at' => Carbon::now()->addDays(1)->addHour(),
        ]);

        $newStart = Carbon::now()->addDays(2);
        $newEnd = Carbon::now()->addDays(2)->addHour();

        $rescheduled = $this->eventService->rescheduleEvent($event, $newStart, $newEnd);

        $this->assertEquals($newStart->toDateTimeString(), $rescheduled->start_at->toDateTimeString());
        $this->assertEquals($newEnd->toDateTimeString(), $rescheduled->end_at->toDateTimeString());
    }

    public function test_can_add_attendees()
    {
        $event = CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $this->calendar->id,
            'user_id' => $this->user->id,
        ]);

        $attendees = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ];

        $updated = $this->eventService->addAttendees($event, $attendees);

        $this->assertCount(2, $updated->attendees);
    }

    public function test_can_remove_attendees()
    {
        $event = CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $this->calendar->id,
            'user_id' => $this->user->id,
            'attendees' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ],
        ]);

        $updated = $this->eventService->removeAttendees($event, [1]);

        $this->assertCount(1, $updated->attendees);
        $this->assertEquals(2, $updated->attendees[0]['id']);
    }

    public function test_get_color_for_type()
    {
        $this->assertEquals('#3B82F6', $this->eventService->getColorForType('meeting'));
        $this->assertEquals('#10B981', $this->eventService->getColorForType('call'));
        $this->assertEquals('#F59E0B', $this->eventService->getColorForType('task'));
        $this->assertEquals('#EF4444', $this->eventService->getColorForType('deadline'));
        $this->assertEquals('#8B5CF6', $this->eventService->getColorForType('leave'));
        $this->assertEquals('#EC4899', $this->eventService->getColorForType('milestone'));
        $this->assertEquals('#6B7280', $this->eventService->getColorForType('personal'));
        $this->assertEquals('#6366F1', $this->eventService->getColorForType('company'));
    }

    public function test_event_is_all_day()
    {
        $event = CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $this->calendar->id,
            'user_id' => $this->user->id,
            'all_day' => true,
        ]);

        $this->assertTrue($event->isAllDay());
    }

    public function test_event_conflicts_with_another()
    {
        $event1 = CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $this->calendar->id,
            'user_id' => $this->user->id,
            'start_at' => Carbon::now()->addHours(1),
            'end_at' => Carbon::now()->addHours(2),
        ]);

        $event2 = CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $this->calendar->id,
            'user_id' => $this->user->id,
            'start_at' => Carbon::now()->addHours(1)->addMinutes(30),
            'end_at' => Carbon::now()->addHours(2)->addMinutes(30),
        ]);

        $this->assertTrue($event1->conflictsWith($event2));
    }
}
