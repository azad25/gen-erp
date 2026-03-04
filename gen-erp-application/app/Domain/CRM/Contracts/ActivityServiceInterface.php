<?php

namespace App\Domain\CRM\Contracts;

use App\Domain\CRM\DTOs\ActivityData;
use App\Domain\CRM\Models\CrmActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ActivityServiceInterface
{
    public function create(ActivityData $data, int $companyId, int $userId): CrmActivity;
    
    public function update(CrmActivity $activity, ActivityData $data): CrmActivity;
    
    public function delete(CrmActivity $activity): bool;
    
    public function findById(int $id, int $companyId): ?CrmActivity;
    
    public function findByUuid(string $uuid, int $companyId): ?CrmActivity;
    
    public function getForCompany(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    public function getForSubject(string $subjectType, int $subjectId, int $companyId): Collection;
    
    public function getForUser(int $userId, int $companyId, array $filters = []): Collection;
    
    public function getScheduled(int $companyId, ?\DateTime $date = null): Collection;
    
    public function getOverdue(int $companyId): Collection;
    
    public function getDueToday(int $companyId): Collection;
    
    public function start(CrmActivity $activity): CrmActivity;
    
    public function complete(CrmActivity $activity, ?string $outcome = null, ?string $notes = null): CrmActivity;
    
    public function cancel(CrmActivity $activity, ?string $reason = null): CrmActivity;
    
    public function reschedule(CrmActivity $activity, \DateTime $newDateTime): CrmActivity;
    
    public function bulkComplete(array $activityIds, int $companyId): int;
    
    public function bulkReschedule(array $activityIds, \DateTime $newDateTime, int $companyId): int;
    
    public function getActivityStatistics(int $companyId, array $filters = []): array;
}