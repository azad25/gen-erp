<?php

namespace Tests\Feature\Domain\Calendar;

use App\Domain\Calendar\Models\Calendar;
use App\Domain\Calendar\Models\CalendarEvent;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, [
            'role' => 'admin',
            'is_owner' => true,
            'is_active' => true,
        ]);
        
        session(['active_company_id' => $this->company->id]);
    }

    public function test_can_list_calendars()
    {
        Calendar::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/calendar');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_calendar()
    {
        $data = [
            'name' => 'My Calendar',
            'description' => 'Personal calendar',
            'type' => 'personal',
            'color' => '#3B82F6',
            'is_public' => false,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/calendar', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'My Calendar')
            ->assertJsonPath('data.type', 'personal');
    }

    public function test_can_show_calendar()
    {
        $calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/calendar/{$calendar->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $calendar->id);
    }

    public function test_can_update_calendar()
    {
        $calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/calendar/{$calendar->id}", [
                'name' => 'New Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_can_delete_calendar()
    {
        $calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/calendar/{$calendar->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted($calendar);
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

        $startDate = Carbon::now()->toDateString();
        $endDate = Carbon::now()->addDays(7)->toDateString();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/calendar/{$calendar->id}/events?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
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

        $startDate = Carbon::now()->toDateString();
        $endDate = Carbon::now()->addDays(7)->toDateString();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/calendar/user-events?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
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

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/calendar/{$calendar->id}/upcoming?limit=3");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_calendar_statistics()
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

        $startDate = Carbon::now()->toDateString();
        $endDate = Carbon::now()->addDays(7)->toDateString();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/calendar/{$calendar->id}/statistics?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
            ->assertJsonPath('data.total_events', 5);
    }

    public function test_can_create_event()
    {
        $calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $data = [
            'calendar_id' => $calendar->id,
            'title' => 'Team Meeting',
            'description' => 'Weekly sync',
            'start_at' => Carbon::now()->addDays(1)->toDateTimeString(),
            'end_at' => Carbon::now()->addDays(1)->addHour()->toDateTimeString(),
            'type' => 'meeting',
            'all_day' => false,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/events', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Team Meeting')
            ->assertJsonPath('data.type', 'meeting');
    }

    public function test_can_update_event()
    {
        $calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $event = CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $calendar->id,
            'user_id' => $this->user->id,
            'title' => 'Old Title',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/events/{$event->id}", [
                'title' => 'New Title',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'New Title');
    }

    public function test_can_delete_event()
    {
        $calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $event = CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $calendar->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/events/{$event->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted($event);
    }

    public function test_can_complete_event()
    {
        $calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $event = CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $calendar->id,
            'user_id' => $this->user->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/events/{$event->id}/complete");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_can_cancel_event()
    {
        $calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $event = CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $calendar->id,
            'user_id' => $this->user->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/events/{$event->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_can_reschedule_event()
    {
        $calendar = Calendar::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $event = CalendarEvent::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $calendar->id,
            'user_id' => $this->user->id,
            'start_at' => Carbon::now()->addDays(1),
        ]);

        $newStart = Carbon::now()->addDays(2)->toDateTimeString();
        $newEnd = Carbon::now()->addDays(2)->addHour()->toDateTimeString();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/events/{$event->id}/reschedule", [
                'start_at' => $newStart,
                'end_at' => $newEnd,
            ]);

        $response->assertStatus(200);
    }

    public function test_can_check_conflicts()
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

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/calendar/{$calendar->id}/check-conflicts", [
                'start_at' => Carbon::now()->addHours(1)->addMinutes(30)->toDateTimeString(),
                'end_at' => Carbon::now()->addHours(2)->addMinutes(30)->toDateTimeString(),
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.has_conflicts', true);
    }

    public function test_validation_fails_for_invalid_calendar_data()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/calendar', [
                'name' => '', // Required field
                'type' => 'invalid', // Invalid type
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type']);
    }

    public function test_validation_fails_for_invalid_event_data()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/events', [
                'title' => '', // Required field
                'type' => 'invalid', // Invalid type
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['calendar_id', 'title', 'start_at', 'type']);
    }
}
