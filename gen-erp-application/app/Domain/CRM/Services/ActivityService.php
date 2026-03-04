<?php

namespace App\Domain\CRM\Services;

use App\Domain\CRM\Contracts\ActivityServiceInterface;
use App\Domain\CRM\DTOs\ActivityData;
use App\Domain\CRM\Enums\ActivityType;
use App\Domain\CRM\Models\CrmActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ActivityService implements ActivityServiceInterface
{
    public function create(ActivityData $data, int $companyId, int $userId): CrmActivity
    {
        $activityData = array_merge($data->toArray(), [
            'company_id' => $companyId,
            'user_id' => $userId,
        ]);

        return CrmActivity::create($activityData);
    }

    public function update(CrmActivity $activity, ActivityData $data): CrmActivity
    {
        $activity->update($data->toArray());
        return $activity->fresh();
    }

    public function delete(CrmActivity $activity): bool
    {
        return $activity->delete();
    }

    public function findById(int $id, int $companyId): ?CrmActivity
    {
        return CrmActivity::forCompany($companyId)->find($id);
    }

    public function findByUuid(string $uuid, int $companyId): ?CrmActivity
    {
        return CrmActivity::forCompany($companyId)->where('uuid', $uuid)->first();
    }

    public function getForCompany(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CrmActivity::forCompany($companyId)
            ->with(['user', 'subject']);

        // Apply filters
        if (isset($filters['type'])) {
            $query->byType(ActivityType::from($filters['type']));
        }

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (isset($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (isset($filters['scheduled_from'])) {
            $query->where('scheduled_at', '>=', $filters['scheduled_from']);
        }

        if (isset($filters['scheduled_to'])) {
            $query->where('scheduled_at', '<=', $filters['scheduled_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'scheduled_at';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    public function getForSubject(string $subjectType, int $subjectId, int $companyId): Collection
    {
        return CrmActivity::forCompany($companyId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getForUser(int $userId, int $companyId, array $filters = []): Collection
    {
        $query = CrmActivity::forCompany($companyId)
            ->where('user_id', $userId)
            ->with(['subject']);

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['type'])) {
            $query->byType(ActivityType::from($filters['type']));
        }

        return $query->orderBy('scheduled_at', 'asc')->get();
    }

    public function getScheduled(int $companyId, ?\DateTime $date = null): Collection
    {
        $query = CrmActivity::forCompany($companyId)->scheduled();

        if ($date) {
            $query->whereDate('scheduled_at', $date);
        }

        return $query->with(['user', 'subject'])
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    public function getOverdue(int $companyId): Collection
    {
        return CrmActivity::forCompany($companyId)
            ->overdue()
            ->with(['user', 'subject'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function getDueToday(int $companyId): Collection
    {
        return CrmActivity::forCompany($companyId)
            ->dueToday()
            ->with(['user', 'subject'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function start(CrmActivity $activity): CrmActivity
    {
        $activity->start();
        return $activity->fresh();
    }

    public function complete(CrmActivity $activity, ?string $outcome = null, ?string $notes = null): CrmActivity
    {
        $activity->complete($outcome, $notes);
        return $activity->fresh();
    }

    public function cancel(CrmActivity $activity, ?string $reason = null): CrmActivity
    {
        $activity->cancel($reason);
        return $activity->fresh();
    }

    public function reschedule(CrmActivity $activity, \DateTime $newDateTime): CrmActivity
    {
        $activity->reschedule($newDateTime);
        return $activity->fresh();
    }

    public function bulkComplete(array $activityIds, int $companyId): int
    {
        return CrmActivity::forCompany($companyId)
            ->whereIn('id', $activityIds)
            ->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
    }

    public function bulkReschedule(array $activityIds, \DateTime $newDateTime, int $companyId): int
    {
        return CrmActivity::forCompany($companyId)
            ->whereIn('id', $activityIds)
            ->update([
                'scheduled_at' => $newDateTime,
                'status' => 'scheduled',
            ]);
    }

    public function getActivityStatistics(int $companyId, array $filters = []): array
    {
        // Helper function to create base query with filters
        $createBaseQuery = function() use ($companyId, $filters) {
            $query = CrmActivity::forCompany($companyId);
            
            // Apply date filters
            if (isset($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }
            
            return $query;
        };

        $total = $createBaseQuery()->count();
        $completed = $createBaseQuery()->completed()->count();
        $overdue = $createBaseQuery()->overdue()->count();
        $dueToday = $createBaseQuery()->dueToday()->count();

        $byType = $createBaseQuery()->groupBy('type')
            ->selectRaw('type, count(*) as count')
            ->pluck('count', 'type')
            ->toArray();

        $byStatus = $createBaseQuery()->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status')
            ->toArray();

        $byUser = $createBaseQuery()->join('users', 'crm_activities.user_id', '=', 'users.id')
            ->groupBy('users.name')
            ->selectRaw('users.name as user_name, count(*) as count')
            ->pluck('count', 'user_name')
            ->toArray();

        $completionRate = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

        // Average completion time for completed activities
        $avgCompletionTime = $createBaseQuery()
            ->completed()
            ->whereNotNull('duration_minutes')
            ->avg('duration_minutes') ?? 0;

        return [
            'total_activities' => $total,
            'completed_activities' => $completed,
            'overdue_activities' => $overdue,
            'due_today' => $dueToday,
            'by_type' => $byType,
            'by_status' => $byStatus,
            'by_user' => $byUser,
            'completion_rate' => $completionRate,
            'average_completion_time' => round($avgCompletionTime, 2),
        ];
    }
}