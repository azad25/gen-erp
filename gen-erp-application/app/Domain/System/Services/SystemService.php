<?php

namespace App\Domain\System\Services;

use App\Domain\System\Contracts\SystemServiceInterface;
use App\Domain\System\Models\ImportJob;
use App\Models\Notification;
use App\Models\CustomField;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service for system operations like import jobs, notifications, etc.
 */
class SystemService implements SystemServiceInterface
{
    /**
     * Get paginated import jobs for a company.
     */
    public function getImportJobs(int $companyId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return ImportJob::query()
            ->where('company_id', $companyId)
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get a specific import job.
     */
    public function getImportJob(int $companyId, int $id): ImportJob
    {
        return ImportJob::where('company_id', $companyId)->findOrFail($id);
    }

    /**
     * Get paginated notifications for a user.
     */
    public function getUserNotifications(int $userId, int $companyId, int $perPage = 15): LengthAwarePaginator
    {
        return Notification::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Mark notification as read.
     */
    public function markNotificationAsRead(Notification $notification): Notification
    {
        $notification->update(['read_at' => now()]);
        return $notification->fresh();
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllNotificationsAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get paginated custom fields for a company.
     */
    public function getCustomFields(int $companyId, ?string $entityType = null, ?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return CustomField::query()
            ->where('company_id', $companyId)
            ->when($entityType, fn ($q, $s) => $q->where('entity_type', $s))
            ->when($search, fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Create a new custom field.
     */
    public function createCustomField(array $data): CustomField
    {
        return CustomField::create($data);
    }

    /**
     * Update a custom field.
     */
    public function updateCustomField(CustomField $customField, array $data): CustomField
    {
        $customField->update($data);
        return $customField->fresh();
    }

    /**
     * Delete a custom field.
     */
    public function deleteCustomField(CustomField $customField): bool
    {
        return $customField->delete();
    }
}