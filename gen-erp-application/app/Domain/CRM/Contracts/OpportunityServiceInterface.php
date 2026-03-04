<?php

namespace App\Domain\CRM\Contracts;

use App\Domain\CRM\DTOs\OpportunityData;
use App\Domain\CRM\Models\Opportunity;
use App\Domain\CRM\Models\PipelineStage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OpportunityServiceInterface
{
    public function create(OpportunityData $data, int $companyId, int $createdBy): Opportunity;
    
    public function update(Opportunity $opportunity, OpportunityData $data): Opportunity;
    
    public function delete(Opportunity $opportunity): bool;
    
    public function findById(int $id, int $companyId): ?Opportunity;
    
    public function findByUuid(string $uuid, int $companyId): ?Opportunity;
    
    public function getForCompany(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    public function getForPipeline(int $pipelineId, int $companyId): Collection;
    
    public function getForStage(int $stageId, int $companyId): Collection;
    
    public function moveToStage(Opportunity $opportunity, PipelineStage $stage, ?string $reason = null): Opportunity;
    
    public function markAsWon(Opportunity $opportunity, ?string $reason = null): Opportunity;
    
    public function markAsLost(Opportunity $opportunity, ?string $reason = null): Opportunity;
    
    public function assignTo(Opportunity $opportunity, int $userId): Opportunity;
    
    public function updateProbability(Opportunity $opportunity, int $probability): Opportunity;
    
    public function bulkMoveToStage(array $opportunityIds, int $stageId, int $companyId): int;
    
    public function bulkAssign(array $opportunityIds, int $userId, int $companyId): int;
    
    public function getOpportunityStatistics(int $companyId, array $filters = []): array;
    
    public function getForecast(int $companyId, array $filters = []): array;
}