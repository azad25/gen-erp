<?php

namespace App\Http\Controllers\Api\V1\CRM;

use App\Domain\CRM\Contracts\ActivityServiceInterface;
use App\Domain\CRM\DTOs\ActivityData;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\CreateActivityRequest;
use App\Http\Requests\CRM\UpdateActivityRequest;
use App\Http\Resources\CRM\ActivityResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityController extends Controller
{
    public function __construct(
        private readonly ActivityServiceInterface $activityService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $companyId = $request->user()->currentCompany->id;
        
        $filters = $request->only([
            'type', 'status', 'user_id', 'subject_type', 'subject_id',
            'scheduled_from', 'scheduled_to', 'search', 'sort_by', 'sort_order'
        ]);
        
        $perPage = $request->get('per_page', 15);
        
        $activities = $this->activityService->getForCompany($companyId, $filters, $perPage);
        
        return ActivityResource::collection($activities);
    }

    public function store(CreateActivityRequest $request): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        $userId = $request->user()->id;
        
        $activityData = ActivityData::fromArray($request->validated());
        $activity = $this->activityService->create($activityData, $companyId, $userId);
        
        // Load the subject relationship for the resource
        $activity->load('subject');
        
        return response()->json([
            'message' => __('crm.activity_created_successfully'),
            'data' => new ActivityResource($activity)
        ], 201);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $activity = $this->activityService->findByUuid($uuid, $companyId);
        
        if (!$activity) {
            return response()->json([
                'message' => __('crm.activity_not_found')
            ], 404);
        }
        
        return response()->json([
            'data' => new ActivityResource($activity->load(['user', 'subject']))
        ]);
    }

    public function update(UpdateActivityRequest $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $activity = $this->activityService->findByUuid($uuid, $companyId);
        
        if (!$activity) {
            return response()->json([
                'message' => __('crm.activity_not_found')
            ], 404);
        }
        
        // Merge existing activity data with update data for partial updates
        $updateData = $request->validated();
        $existingData = [
            'type' => $activity->type->value,
            'title' => $activity->title,
            'subject_type' => $activity->subject_type,
            'subject_id' => $activity->subject_id,
            'description' => $activity->description,
            'status' => $activity->status,
            'priority' => $activity->priority,
            'scheduled_at' => $activity->scheduled_at?->format('Y-m-d H:i:s'),
            'due_date' => $activity->due_date?->format('Y-m-d'),
            'planned_duration_minutes' => $activity->planned_duration_minutes,
            'direction' => $activity->direction,
            'is_reminder' => $activity->is_reminder,
            'reminder_at' => $activity->reminder_at?->format('Y-m-d H:i:s'),
            'email_subject' => $activity->email_subject,
            'email_body' => $activity->email_body,
            'attachments' => $activity->attachments,
            'meeting_location' => $activity->meeting_location,
            'meeting_link' => $activity->meeting_link,
            'attendees' => $activity->attendees,
            'custom_fields' => $activity->custom_fields,
            'metadata' => $activity->metadata,
        ];
        
        $mergedData = array_merge($existingData, $updateData);
        $activityData = ActivityData::fromArray($mergedData);
        $activity = $this->activityService->update($activity, $activityData);
        
        return response()->json([
            'message' => __('crm.activity_updated_successfully'),
            'data' => new ActivityResource($activity)
        ]);
    }

    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $activity = $this->activityService->findByUuid($uuid, $companyId);
        
        if (!$activity) {
            return response()->json([
                'message' => __('crm.activity_not_found')
            ], 404);
        }
        
        $this->activityService->delete($activity);
        
        return response()->json([
            'message' => __('crm.activity_deleted_successfully')
        ]);
    }

    public function start(Request $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $activity = $this->activityService->findByUuid($uuid, $companyId);
        
        if (!$activity) {
            return response()->json([
                'message' => __('crm.activity_not_found')
            ], 404);
        }
        
        $activity = $this->activityService->start($activity);
        
        return response()->json([
            'message' => __('crm.activity_started_successfully'),
            'data' => new ActivityResource($activity)
        ]);
    }

    public function complete(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'outcome' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        
        $activity = $this->activityService->findByUuid($uuid, $companyId);
        
        if (!$activity) {
            return response()->json([
                'message' => __('crm.activity_not_found')
            ], 404);
        }
        
        $activity = $this->activityService->complete($activity, $request->outcome, $request->notes);
        
        return response()->json([
            'message' => __('crm.activity_completed_successfully'),
            'data' => new ActivityResource($activity)
        ]);
    }

    public function cancel(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        
        $activity = $this->activityService->findByUuid($uuid, $companyId);
        
        if (!$activity) {
            return response()->json([
                'message' => __('crm.activity_not_found')
            ], 404);
        }
        
        $activity = $this->activityService->cancel($activity, $request->reason);
        
        return response()->json([
            'message' => __('crm.activity_cancelled_successfully'),
            'data' => new ActivityResource($activity)
        ]);
    }

    public function reschedule(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'scheduled_at' => 'required|date|after:now'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        
        $activity = $this->activityService->findByUuid($uuid, $companyId);
        
        if (!$activity) {
            return response()->json([
                'message' => __('crm.activity_not_found')
            ], 404);
        }
        
        $activity = $this->activityService->reschedule($activity, new \DateTime($request->scheduled_at));
        
        return response()->json([
            'message' => __('crm.activity_rescheduled_successfully'),
            'data' => new ActivityResource($activity)
        ]);
    }

    public function forSubject(Request $request, string $subjectType, int $subjectId): AnonymousResourceCollection
    {
        $companyId = $request->user()->currentCompany->id;
        
        $activities = $this->activityService->getForSubject($subjectType, $subjectId, $companyId);
        
        return ActivityResource::collection($activities);
    }

    public function myActivities(Request $request): AnonymousResourceCollection
    {
        $companyId = $request->user()->currentCompany->id;
        $userId = $request->user()->id;
        
        $filters = $request->only(['status', 'type']);
        $activities = $this->activityService->getForUser($userId, $companyId, $filters);
        
        return ActivityResource::collection($activities);
    }

    public function scheduled(Request $request): AnonymousResourceCollection
    {
        $companyId = $request->user()->currentCompany->id;
        
        $date = $request->date ? new \DateTime($request->date) : null;
        $activities = $this->activityService->getScheduled($companyId, $date);
        
        return ActivityResource::collection($activities);
    }

    public function overdue(Request $request): AnonymousResourceCollection
    {
        $companyId = $request->user()->currentCompany->id;
        
        $activities = $this->activityService->getOverdue($companyId);
        
        return ActivityResource::collection($activities);
    }

    public function dueToday(Request $request): AnonymousResourceCollection
    {
        $companyId = $request->user()->currentCompany->id;
        
        $activities = $this->activityService->getDueToday($companyId);
        
        return ActivityResource::collection($activities);
    }

    public function bulkComplete(Request $request): JsonResponse
    {
        $request->validate([
            'activity_ids' => 'required|array',
            'activity_ids.*' => 'integer|exists:crm_activities,id'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        
        $updated = $this->activityService->bulkComplete($request->activity_ids, $companyId);
        
        return response()->json([
            'message' => __('crm.activities_completed_successfully', ['count' => $updated])
        ]);
    }

    public function bulkReschedule(Request $request): JsonResponse
    {
        $request->validate([
            'activity_ids' => 'required|array',
            'activity_ids.*' => 'integer|exists:crm_activities,id',
            'scheduled_at' => 'required|date|after:now'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        
        $updated = $this->activityService->bulkReschedule(
            $request->activity_ids,
            new \DateTime($request->scheduled_at),
            $companyId
        );
        
        return response()->json([
            'message' => __('crm.activities_rescheduled_successfully', ['count' => $updated])
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $filters = $request->only(['date_from', 'date_to']);
        $statistics = $this->activityService->getActivityStatistics($companyId, $filters);
        
        return response()->json([
            'data' => $statistics
        ]);
    }
}