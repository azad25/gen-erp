<?php

namespace Tests\Unit\Domain\CRM\Services;

use App\Domain\CRM\Contracts\ActivityServiceInterface;
use App\Domain\CRM\DTOs\ActivityData;
use App\Domain\CRM\Enums\ActivityType;
use App\Domain\CRM\Models\CrmActivity;
use App\Domain\CRM\Models\Lead;
use App\Domain\CRM\Services\ActivityService;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActivityServiceInterface $activityService;
    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->activityService = new ActivityService();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        
        // Associate user with company
        $this->user->companies()->attach($this->company->id, ['role' => 'admin']);
    }

    public function test_can_create_activity(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $activityData = new ActivityData(
            type: ActivityType::CALL,
            title: 'Follow-up call',
            description: 'Call to discuss proposal',
            scheduledAt: now()->addHour(),
            dueDate: now()->addDay(),
            subjectType: 'lead',
            subjectId: $lead->id,
            priority: 'high'
        );

        $activity = $this->activityService->create($activityData, $this->company->id, $this->user->id);

        $this->assertInstanceOf(CrmActivity::class, $activity);
        $this->assertEquals(ActivityType::CALL, $activity->type);
        $this->assertEquals('Follow-up call', $activity->title);
        $this->assertEquals('Call to discuss proposal', $activity->description);
        $this->assertEquals($this->company->id, $activity->company_id);
        $this->assertEquals($this->user->id, $activity->user_id);
        $this->assertEquals('lead', $activity->subject_type);
        $this->assertEquals($lead->id, $activity->subject_id);
        $this->assertEquals('high', $activity->priority);
        $this->assertEquals('scheduled', $activity->status);
    }

    public function test_can_update_activity(): void
    {
        $activity = CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'title' => 'Original Title',
        ]);

        $activityData = new ActivityData(
            type: ActivityType::CALL,
            title: 'Updated Title',
            subjectType: 'lead',
            subjectId: 1,
            description: 'Updated description',
            priority: 'medium',
            scheduledAt: now()->addHours(2)
        );

        $updatedActivity = $this->activityService->update($activity, $activityData);

        $this->assertEquals('Updated Title', $updatedActivity->title);
        $this->assertEquals('Updated description', $updatedActivity->description);
        $this->assertEquals('medium', $updatedActivity->priority);
    }

    public function test_can_find_activity_by_uuid(): void
    {
        $activity = CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $foundActivity = $this->activityService->findByUuid($activity->uuid, $this->company->id);

        $this->assertNotNull($foundActivity);
        $this->assertEquals($activity->id, $foundActivity->id);
        $this->assertEquals($activity->uuid, $foundActivity->uuid);
    }

    public function test_cannot_find_activity_from_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $activity = CrmActivity::factory()->create([
            'company_id' => $otherCompany->id,
            'user_id' => $this->user->id,
        ]);

        $foundActivity = $this->activityService->findByUuid($activity->uuid, $this->company->id);

        $this->assertNull($foundActivity);
    }

    public function test_can_start_activity(): void
    {
        $activity = CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'scheduled',
            'started_at' => null,
        ]);

        $startedActivity = $this->activityService->start($activity);

        $this->assertEquals('in_progress', $startedActivity->status);
        $this->assertNotNull($startedActivity->started_at);
    }

    public function test_can_complete_activity(): void
    {
        $activity = CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        $completedActivity = $this->activityService->complete($activity, 'Successful call', 'Customer is interested');

        $this->assertEquals('completed', $completedActivity->status);
        $this->assertNotNull($completedActivity->completed_at);
        $this->assertEquals('Successful call', $completedActivity->outcome);
        $this->assertEquals('Customer is interested', $completedActivity->outcome_notes);
    }

    public function test_can_cancel_activity(): void
    {
        $activity = CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'scheduled',
        ]);

        $cancelledActivity = $this->activityService->cancel($activity, 'Customer unavailable');

        $this->assertEquals('cancelled', $cancelledActivity->status);
        $this->assertEquals('Customer unavailable', $cancelledActivity->outcome_notes);
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

        $rescheduledActivity = $this->activityService->reschedule($activity, $newTime);

        $this->assertEquals($newTime->format('Y-m-d H:i:s'), $rescheduledActivity->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertEquals('scheduled', $rescheduledActivity->status);
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
            'subject_type' => 'lead',
            'subject_id' => $lead->id,
        ]);

        // Create activity for different subject
        CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'subject_type' => 'opportunity',
            'subject_id' => 999,
        ]);

        $activities = $this->activityService->getForSubject(
            'lead',
            $lead->id,
            $this->company->id
        );

        $this->assertEquals(3, $activities->count());
        foreach ($activities as $activity) {
            $this->assertEquals('lead', $activity->subject_type);
            $this->assertEquals($lead->id, $activity->subject_id);
        }
    }

    public function test_can_get_activities_for_user(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->companies()->attach($this->company->id, ['role' => 'user']);

        CrmActivity::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        CrmActivity::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $otherUser->id,
        ]);

        $userActivities = $this->activityService->getForUser($this->user->id, $this->company->id);

        $this->assertEquals(2, $userActivities->count());
        foreach ($userActivities as $activity) {
            $this->assertEquals($this->user->id, $activity->user_id);
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

        $scheduledActivities = $this->activityService->getScheduled($this->company->id, $today);

        $this->assertEquals(2, $scheduledActivities->count());
        foreach ($scheduledActivities as $activity) {
            $this->assertEquals('scheduled', $activity->status);
            $this->assertEquals($today->format('Y-m-d'), $activity->scheduled_at->format('Y-m-d'));
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

        $overdueActivities = $this->activityService->getOverdue($this->company->id);

        $this->assertEquals(2, $overdueActivities->count());
        foreach ($overdueActivities as $activity) {
            $this->assertEquals('scheduled', $activity->status);
            $this->assertTrue($activity->due_date->isPast());
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

        $dueTodayActivities = $this->activityService->getDueToday($this->company->id);

        $this->assertEquals(2, $dueTodayActivities->count());
        foreach ($dueTodayActivities as $activity) {
            $this->assertEquals('scheduled', $activity->status);
            $this->assertEquals($today->format('Y-m-d'), $activity->due_date->format('Y-m-d'));
        }
    }

    public function test_can_bulk_complete_activities(): void
    {
        $activities = CrmActivity::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'status' => 'scheduled',
        ]);

        $activityIds = $activities->pluck('id')->toArray();
        $updated = $this->activityService->bulkComplete($activityIds, $this->company->id);

        $this->assertEquals(3, $updated);
        
        foreach ($activities as $activity) {
            $activity->refresh();
            $this->assertEquals('completed', $activity->status);
            $this->assertNotNull($activity->completed_at);
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

        $activityIds = $activities->pluck('id')->toArray();
        $updated = $this->activityService->bulkReschedule($activityIds, $newDateTime, $this->company->id);

        $this->assertEquals(3, $updated);
        
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
            'due_date' => null, // Explicitly set to null to avoid random due dates
        ]);

        // Create overdue activities - must have status != 'completed' and due_date in past
        CrmActivity::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => ActivityType::EMAIL,
            'status' => 'scheduled', // Important: not completed
            'due_date' => now()->subDay(), // overdue (yesterday)
            'scheduled_at' => now()->subDay(),
        ]);

        CrmActivity::factory()->count(1)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => ActivityType::MEETING,
            'status' => 'scheduled',
            'due_date' => now()->endOfDay(), // due today but not overdue
            'scheduled_at' => now()->endOfDay(),
        ]);

        $statistics = $this->activityService->getActivityStatistics($this->company->id);

        $this->assertEquals(6, $statistics['total_activities']);
        $this->assertEquals(3, $statistics['completed_activities']);
        $this->assertEquals(2, $statistics['overdue_activities']);
        $this->assertEquals(1, $statistics['due_today']);
        $this->assertEquals(3, $statistics['by_type'][ActivityType::CALL->value]);
        $this->assertEquals(2, $statistics['by_type'][ActivityType::EMAIL->value]);
        $this->assertEquals(1, $statistics['by_type'][ActivityType::MEETING->value]);
        $this->assertEquals(3, $statistics['by_status']['completed']);
        $this->assertEquals(3, $statistics['by_status']['scheduled']);
        $this->assertEquals(50.0, $statistics['completion_rate']); // 3/6 * 100
        $this->assertEquals(30.0, $statistics['average_completion_time']); // Average of 30 minutes
    }

    public function test_can_get_activities_with_filters(): void
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
        $callActivities = $this->activityService->getForCompany($this->company->id, [
            'type' => ActivityType::CALL->value,
        ]);
        $this->assertEquals(1, $callActivities->count());

        // Test status filter
        $completedActivities = $this->activityService->getForCompany($this->company->id, [
            'status' => 'completed',
        ]);
        $this->assertEquals(1, $completedActivities->count());

        // Test search filter
        $searchResults = $this->activityService->getForCompany($this->company->id, [
            'search' => 'Important',
        ]);
        $this->assertEquals(1, $searchResults->count());
    }
}