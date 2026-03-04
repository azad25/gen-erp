<?php

namespace Tests\Feature\Domain\CRM;

use App\Domain\CRM\Enums\ActivityType;
use App\Domain\CRM\Models\CrmActivity;
use App\Domain\CRM\Models\Lead;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityApiTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        
        // Associate user with company and set as current company
        $this->user->companies()->attach($this->company->id, ['role' => 'admin']);
        $this->user->update(['current_company_id' => $this->company->id]);
        
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_activities(): void
    {
        CrmActivity::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/crm/activities');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'type',
                        'title',
                        'description',
                        'status',
                        'priority',
                        'scheduled_at',
                        'due_date',
                        'user',
                        'subject',
                        'created_at',
                    ]
                ],
                'links',
                'meta',
            ]);

        $this->assertEquals(3, $response->json('meta.total'));
    }

    public function test_can_create_activity(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $activityData = [
            'type' => ActivityType::CALL->value,
            'title' => 'Follow-up call',
            'description' => 'Call to discuss proposal',
            'scheduled_at' => now()->addHour()->toISOString(),
            'due_date' => now()->addDay()->toDateString(),
            'subject_type' => 'App\\Domain\\CRM\\Models\\Lead',
            'subject_id' => $lead->id,
            'priority' => 'high',
        ];

        $response = $this->postJson('/api/v1/crm/activities', $activityData);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'type',
                    'title',
                    'description',
                    'status',
                    'priority',
                    'scheduled_at',
                    'due_date',
                    'subject',
                ]
            ]);

        $this->assertDatabaseHas('crm_activities', [
            'title' => 'Follow-up call',
            'description' => 'Call to discuss proposal',
            'type' => ActivityType::CALL->value,
            'priority' => 'high',
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'subject_type' => 'App\\Domain\\CRM\\Models\\Lead',
            'subject_id' => $lead->id,
        ]);
    }

    public function test_create_activity_validation(): void
    {
        $response = $this->postJson('/api/v1/crm/activities', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type', 'title']);
    }

    public function test_can_show_activity(): void
    {
        $activity = CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/crm/activities/{$activity->uuid}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'type',
                    'title',
                    'description',
                    'status',
                    'priority',
                    'scheduled_at',
                    'due_date',
                    'user',
                    'subject',
                    'created_at',
                ]
            ]);

        $this->assertEquals($activity->uuid, $response->json('data.uuid'));
    }

    public function test_cannot_show_activity_from_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $activity = CrmActivity::factory()->create([
            'company_id' => $otherCompany->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/crm/activities/{$activity->uuid}");

        $response->assertNotFound();
    }

    public function test_can_update_activity(): void
    {
        $activity = CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'title' => 'Original Title',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'priority' => 'medium',
            'scheduled_at' => now()->addHours(2)->toISOString(),
        ];

        $response = $this->putJson("/api/v1/crm/activities/{$activity->uuid}", $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'title',
                    'description',
                    'priority',
                    'scheduled_at',
                ]
            ]);

        $this->assertDatabaseHas('crm_activities', [
            'id' => $activity->id,
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'priority' => 'medium',
        ]);
    }

    public function test_can_delete_activity(): void
    {
        $activity = CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/v1/crm/activities/{$activity->uuid}");

        $response->assertOk()
            ->assertJson(['message' => __('crm.activity_deleted_successfully')]);

        $this->assertSoftDeleted('crm_activities', ['id' => $activity->id]);
    }

    public function test_can_start_activity(): void
    {
        $activity = CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'scheduled',
            'started_at' => null,
        ]);

        $response = $this->postJson("/api/v1/crm/activities/{$activity->uuid}/start");

        $response->assertOk()
            ->assertJson(['message' => __('crm.activity_started_successfully')]);

        $activity->refresh();
        $this->assertEquals('in_progress', $activity->status);
        $this->assertNotNull($activity->started_at);
    }

    public function test_can_complete_activity(): void
    {
        $activity = CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        $response = $this->postJson("/api/v1/crm/activities/{$activity->uuid}/complete", [
            'outcome' => 'Successful call',
            'notes' => 'Customer is interested',
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.activity_completed_successfully')]);

        $activity->refresh();
        $this->assertEquals('completed', $activity->status);
        $this->assertNotNull($activity->completed_at);
        $this->assertEquals('Successful call', $activity->outcome);
        $this->assertEquals('Customer is interested', $activity->outcome_notes);
    }

    public function test_can_cancel_activity(): void
    {
        $activity = CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'scheduled',
        ]);

        $response = $this->postJson("/api/v1/crm/activities/{$activity->uuid}/cancel", [
            'reason' => 'Customer unavailable',
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.activity_cancelled_successfully')]);

        $activity->refresh();
        $this->assertEquals('cancelled', $activity->status);
        $this->assertEquals('Customer unavailable', $activity->outcome_notes);
    }

    public function test_can_reschedule_activity(): void
    {
        $originalTime = now()->addHour();
        $newTime = now()->addHours(3);

        $activity = CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'scheduled_at' => $originalTime,
            'status' => 'scheduled',
        ]);

        $response = $this->postJson("/api/v1/crm/activities/{$activity->uuid}/reschedule", [
            'scheduled_at' => $newTime->toISOString(),
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.activity_rescheduled_successfully')]);

        $activity->refresh();
        $this->assertEquals($newTime->format('Y-m-d H:i:s'), $activity->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertEquals('scheduled', $activity->status);
    }

    public function test_can_get_activities_for_subject(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        CrmActivity::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'subject_type' => 'App\\Domain\\CRM\\Models\\Lead',
            'subject_id' => $lead->id,
        ]);

        // Create activity for different subject
        CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'subject_type' => 'App\\Domain\\CRM\\Models\\Opportunity',
            'subject_id' => 999,
        ]);

        $response = $this->getJson("/api/v1/crm/activities/for-subject/" . urlencode('App\\Domain\\CRM\\Models\\Lead') . "/{$lead->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'type',
                        'title',
                        'subject_type',
                        'subject_id',
                        'user',
                    ]
                ]
            ]);

        $this->assertEquals(3, count($response->json('data')));
        foreach ($response->json('data') as $activity) {
            $this->assertEquals('App\\Domain\\CRM\\Models\\Lead', $activity['subject_type']);
            $this->assertEquals($lead->id, $activity['subject_id']);
        }
    }

    public function test_can_get_scheduled_activities(): void
    {
        $today = now();
        $tomorrow = now()->addDay();

        CrmActivity::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'scheduled',
            'scheduled_at' => $today,
        ]);

        CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'scheduled',
            'scheduled_at' => $tomorrow,
        ]);

        CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'scheduled_at' => $today,
        ]);

        $response = $this->getJson('/api/v1/crm/activities/scheduled?date=' . $today->format('Y-m-d'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'type',
                        'title',
                        'status',
                        'scheduled_at',
                        'user',
                        'subject',
                    ]
                ]
            ]);

        $this->assertEquals(2, count($response->json('data')));
        foreach ($response->json('data') as $activity) {
            $this->assertEquals('scheduled', $activity['status']['value']);
            $this->assertEquals($today->format('Y-m-d'), substr($activity['scheduled_at'], 0, 10));
        }
    }

    public function test_can_get_overdue_activities(): void
    {
        $yesterday = now()->subDay();
        $tomorrow = now()->addDay();

        CrmActivity::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'scheduled',
            'due_date' => $yesterday,
        ]);

        CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'scheduled',
            'due_date' => $tomorrow,
        ]);

        CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'due_date' => $yesterday,
        ]);

        $response = $this->getJson('/api/v1/crm/activities/overdue');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'type',
                        'title',
                        'status',
                        'due_date',
                        'user',
                        'subject',
                    ]
                ]
            ]);

        $this->assertEquals(2, count($response->json('data')));
        foreach ($response->json('data') as $activity) {
            $this->assertEquals('scheduled', $activity['status']['value']);
            $this->assertTrue(now()->parse($activity['due_date'])->isPast());
        }
    }

    public function test_can_get_due_today_activities(): void
    {
        $today = now();
        $tomorrow = now()->addDay();

        CrmActivity::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'scheduled',
            'due_date' => $today,
        ]);

        CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'scheduled',
            'due_date' => $tomorrow,
        ]);

        $response = $this->getJson('/api/v1/crm/activities/due-today');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'type',
                        'title',
                        'status',
                        'due_date',
                        'user',
                        'subject',
                    ]
                ]
            ]);

        $this->assertEquals(2, count($response->json('data')));
        foreach ($response->json('data') as $activity) {
            $this->assertEquals('scheduled', $activity['status']['value']);
            $this->assertEquals($today->format('Y-m-d'), substr($activity['due_date'], 0, 10));
        }
    }

    public function test_can_bulk_complete_activities(): void
    {
        $activities = CrmActivity::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'scheduled',
        ]);

        $response = $this->postJson('/api/v1/crm/activities/bulk-complete', [
            'activity_ids' => $activities->pluck('id')->toArray(),
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.activities_completed_successfully', ['count' => 3])]);

        foreach ($activities as $activity) {
            $this->assertDatabaseHas('crm_activities', [
                'id' => $activity->id,
                'status' => 'completed',
            ]);
        }
    }

    public function test_can_bulk_reschedule_activities(): void
    {
        $newDateTime = now()->addHours(4);
        
        $activities = CrmActivity::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addHour(),
        ]);

        $response = $this->postJson('/api/v1/crm/activities/bulk-reschedule', [
            'activity_ids' => $activities->pluck('id')->toArray(),
            'scheduled_at' => $newDateTime->toISOString(),
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.activities_rescheduled_successfully', ['count' => 3])]);

        foreach ($activities as $activity) {
            $activity->refresh();
            $this->assertEquals('scheduled', $activity->status);
            $this->assertEquals($newDateTime->format('Y-m-d H:i:s'), $activity->scheduled_at->format('Y-m-d H:i:s'));
        }
    }

    public function test_can_get_activity_statistics(): void
    {
        // Create activities with different statuses and types
        CrmActivity::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => ActivityType::CALL,
            'status' => 'completed',
            'duration_minutes' => 30,
            'due_date' => null, // Explicitly set to null to avoid random factory values
        ]);

        CrmActivity::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => ActivityType::EMAIL,
            'status' => 'scheduled',
            'due_date' => now()->subDay()->startOfDay(), // overdue - yesterday
        ]);

        CrmActivity::factory()->count(1)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => ActivityType::MEETING,
            'status' => 'scheduled',
            'due_date' => now()->startOfDay(), // due today
        ]);

        $response = $this->getJson('/api/v1/crm/activities/statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_activities',
                    'completed_activities',
                    'overdue_activities',
                    'due_today',
                    'by_type',
                    'by_status',
                    'by_user',
                    'completion_rate',
                    'average_completion_time',
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(6, $data['total_activities']);
        $this->assertEquals(3, $data['completed_activities']);
        $this->assertEquals(2, $data['overdue_activities']);
        $this->assertEquals(1, $data['due_today']);
        $this->assertEquals(3, $data['by_type'][ActivityType::CALL->value]);
        $this->assertEquals(2, $data['by_type'][ActivityType::EMAIL->value]);
        $this->assertEquals(1, $data['by_type'][ActivityType::MEETING->value]);
        $this->assertEquals(3, $data['by_status']['completed']);
        $this->assertEquals(3, $data['by_status']['scheduled']);
        $this->assertEquals(50.0, $data['completion_rate']); // 3/6 * 100
    }

    public function test_can_filter_activities(): void
    {
        // Create activities with different attributes
        CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => ActivityType::CALL,
            'status' => 'scheduled',
            'title' => 'Important call',
        ]);

        CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => ActivityType::EMAIL,
            'status' => 'completed',
            'title' => 'Follow-up email',
        ]);

        // Test type filter
        $response = $this->getJson('/api/v1/crm/activities?type=' . ActivityType::CALL->value);
        $response->assertOk();
        $this->assertEquals(1, $response->json('meta.total'));

        // Test status filter
        $response = $this->getJson('/api/v1/crm/activities?status=completed');
        $response->assertOk();
        $this->assertEquals(1, $response->json('meta.total'));

        // Test search filter
        $response = $this->getJson('/api/v1/crm/activities?search=Important');
        $response->assertOk();
        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_can_get_my_activities(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->companies()->attach($this->company->id, ['role' => 'user']);

        // Create activities for current user
        CrmActivity::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        // Create activity for other user
        CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->getJson('/api/v1/crm/activities/my-activities');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'type',
                        'title',
                        'user_id',
                        'subject',
                    ]
                ]
            ]);

        $this->assertEquals(2, count($response->json('data')));
        foreach ($response->json('data') as $activity) {
            $this->assertEquals($this->user->id, $activity['user_id']);
        }
    }
}