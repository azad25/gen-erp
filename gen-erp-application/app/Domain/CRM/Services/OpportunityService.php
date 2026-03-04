<?php

namespace App\Domain\CRM\Services;

use App\Domain\CRM\Contracts\OpportunityServiceInterface;
use App\Domain\CRM\DTOs\OpportunityData;
use App\Domain\CRM\Models\Opportunity;
use App\Domain\CRM\Models\PipelineStage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OpportunityService implements OpportunityServiceInterface
{
    public function create(OpportunityData $data, int $companyId, int $createdBy): Opportunity
    {
        return DB::transaction(function () use ($data, $companyId, $createdBy) {
            $opportunityData = array_merge($data->toArray(), [
                'company_id' => $companyId,
                'created_by' => $createdBy,
                'status' => 'open',
            ]);

            // Get stage probability if not provided
            if (!$data->probability) {
                $stage = PipelineStage::find($data->stageId);
                $opportunityData['probability'] = $stage?->probability ?? 10;
            }

            $opportunity = Opportunity::create($opportunityData);

            // Update pipeline and stage metrics
            $opportunity->pipeline->updateMetrics();
            $opportunity->stage->updateMetrics();

            return $opportunity->fresh();
        });
    }

    public function update(Opportunity $opportunity, OpportunityData $data): Opportunity
    {
        return DB::transaction(function () use ($opportunity, $data) {
            $oldPipeline = $opportunity->pipeline;
            $oldStage = $opportunity->stage;

            $opportunity->update($data->toArray());
            $opportunity = $opportunity->fresh();

            // Update metrics for affected pipelines/stages
            if ($opportunity->pipeline_id !== $oldPipeline->id) {
                $oldPipeline->updateMetrics();
                $opportunity->pipeline->updateMetrics();
            }

            if ($opportunity->stage_id !== $oldStage->id) {
                $oldStage->updateMetrics();
                $opportunity->stage->updateMetrics();
            }

            return $opportunity;
        });
    }

    public function delete(Opportunity $opportunity): bool
    {
        return DB::transaction(function () use ($opportunity) {
            $pipeline = $opportunity->pipeline;
            $stage = $opportunity->stage;

            $deleted = $opportunity->delete();

            if ($deleted) {
                $pipeline->updateMetrics();
                $stage->updateMetrics();
            }

            return $deleted;
        });
    }

    public function findById(int $id, int $companyId): ?Opportunity
    {
        return Opportunity::forCompany($companyId)->find($id);
    }

    public function findByUuid(string $uuid, int $companyId): ?Opportunity
    {
        return Opportunity::forCompany($companyId)->where('uuid', $uuid)->first();
    }

    public function getForCompany(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Opportunity::forCompany($companyId)
            ->with(['pipeline', 'stage', 'lead', 'customer', 'assignedTo', 'createdBy']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['pipeline_id'])) {
            $query->inPipeline($filters['pipeline_id']);
        }

        if (isset($filters['stage_id'])) {
            $query->inStage($filters['stage_id']);
        }

        if (isset($filters['assigned_to'])) {
            $query->assignedTo($filters['assigned_to']);
        }

        if (isset($filters['expected_close_from']) && isset($filters['expected_close_to'])) {
            $query->expectedToClose($filters['expected_close_from'], $filters['expected_close_to']);
        }

        if (isset($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }

        if (isset($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    public function getForPipeline(int $pipelineId, int $companyId): Collection
    {
        return Opportunity::forCompany($companyId)
            ->inPipeline($pipelineId)
            ->with(['stage', 'assignedTo'])
            ->orderBy('stage_order')
            ->get();
    }

    public function getForStage(int $stageId, int $companyId): Collection
    {
        return Opportunity::forCompany($companyId)
            ->inStage($stageId)
            ->with(['assignedTo', 'lead', 'customer'])
            ->orderBy('amount', 'desc')
            ->get();
    }

    public function moveToStage(Opportunity $opportunity, PipelineStage $stage, ?string $reason = null): Opportunity
    {
        return DB::transaction(function () use ($opportunity, $stage, $reason) {
            $opportunity->moveToStage($stage, $reason);
            return $opportunity->fresh();
        });
    }

    public function markAsWon(Opportunity $opportunity, ?string $reason = null): Opportunity
    {
        return DB::transaction(function () use ($opportunity, $reason) {
            $opportunity->markAsWon($reason);
            return $opportunity->fresh();
        });
    }

    public function markAsLost(Opportunity $opportunity, ?string $reason = null): Opportunity
    {
        return DB::transaction(function () use ($opportunity, $reason) {
            $opportunity->markAsLost($reason);
            return $opportunity->fresh();
        });
    }

    public function assignTo(Opportunity $opportunity, int $userId): Opportunity
    {
        $opportunity->update(['assigned_to' => $userId]);
        return $opportunity->fresh();
    }

    public function updateProbability(Opportunity $opportunity, int $probability): Opportunity
    {
        $opportunity->update(['probability' => max(0, min(100, $probability))]);
        return $opportunity->fresh();
    }

    public function bulkMoveToStage(array $opportunityIds, int $stageId, int $companyId): int
    {
        return DB::transaction(function () use ($opportunityIds, $stageId, $companyId) {
            $stage = PipelineStage::forCompany($companyId)->find($stageId);
            if (!$stage) return 0;

            $updated = Opportunity::forCompany($companyId)
                ->whereIn('id', $opportunityIds)
                ->update([
                    'stage_id' => $stageId,
                    'probability' => $stage->probability,
                    'stage_order' => $stage->sort_order,
                    'last_activity_at' => now(),
                ]);

            // Update metrics
            $stage->updateMetrics();
            $stage->pipeline->updateMetrics();

            return $updated;
        });
    }

    public function bulkAssign(array $opportunityIds, int $userId, int $companyId): int
    {
        return Opportunity::forCompany($companyId)
            ->whereIn('id', $opportunityIds)
            ->update(['assigned_to' => $userId]);
    }

    public function getOpportunityStatistics(int $companyId, array $filters = []): array
    {
        // Helper function to create base query with filters
        $createBaseQuery = function() use ($companyId, $filters) {
            $query = Opportunity::forCompany($companyId);
            
            // Apply date filters
            if (isset($filters['date_from'])) {
                $query->where('opportunities.created_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('opportunities.created_at', '<=', $filters['date_to']);
            }
            
            return $query;
        };

        $total = $createBaseQuery()->count();
        $totalValue = $createBaseQuery()->sum('opportunities.amount');
        $wonValue = $createBaseQuery()->won()->sum('opportunities.amount');
        $lostValue = $createBaseQuery()->lost()->sum('opportunities.amount');
        $openValue = $createBaseQuery()->open()->sum('opportunities.amount');

        $byStatus = $createBaseQuery()
            ->groupBy('opportunities.status')
            ->selectRaw('opportunities.status, count(*) as count, sum(opportunities.amount) as value')
            ->get()
            ->keyBy('status')
            ->toArray();

        $byStage = Opportunity::where('opportunities.company_id', $companyId)
            ->join('pipeline_stages', 'opportunities.stage_id', '=', 'pipeline_stages.id')
            ->groupBy('pipeline_stages.name')
            ->selectRaw('pipeline_stages.name as stage_name, count(*) as count, sum(opportunities.amount) as value')
            ->get()
            ->keyBy('stage_name')
            ->toArray();

        $conversionRate = $total > 0 
            ? round((($byStatus['won']['count'] ?? 0) / $total) * 100, 2)
            : 0;

        $averageDealSize = $total > 0 ? round($totalValue / $total, 2) : 0;
        $weightedPipelineValue = $createBaseQuery()->open()->get()->sum('weighted_value');

        return [
            'total_opportunities' => $total,
            'total_value' => $totalValue,
            'won_value' => $wonValue,
            'lost_value' => $lostValue,
            'open_value' => $openValue,
            'weighted_pipeline_value' => $weightedPipelineValue,
            'by_status' => $byStatus,
            'by_stage' => $byStage,
            'conversion_rate' => $conversionRate,
            'average_deal_size' => $averageDealSize,
        ];
    }

    public function getForecast(int $companyId, array $filters = []): array
    {
        $query = Opportunity::forCompany($companyId)->open();

        // Apply filters
        if (isset($filters['pipeline_id'])) {
            $query->inPipeline($filters['pipeline_id']);
        }

        if (isset($filters['assigned_to'])) {
            $query->assignedTo($filters['assigned_to']);
        }

        $opportunities = $query->get();

        $forecast = [
            'total_opportunities' => $opportunities->count(),
            'total_value' => $opportunities->sum('amount'),
            'weighted_value' => $opportunities->sum('weighted_value'),
            'by_month' => [],
            'by_probability' => [
                'high' => ['count' => 0, 'value' => 0], // 70%+
                'medium' => ['count' => 0, 'value' => 0], // 30-69%
                'low' => ['count' => 0, 'value' => 0], // <30%
            ],
        ];

        // Group by expected close month
        $byMonth = $opportunities->groupBy(function ($opportunity) {
            return $opportunity->expected_close_date?->format('Y-m') ?? 'unknown';
        });

        foreach ($byMonth as $month => $monthOpportunities) {
            $forecast['by_month'][$month] = [
                'count' => $monthOpportunities->count(),
                'value' => $monthOpportunities->sum('amount'),
                'weighted_value' => $monthOpportunities->sum('weighted_value'),
            ];
        }

        // Group by probability ranges
        foreach ($opportunities as $opportunity) {
            $probability = $opportunity->probability;
            if ($probability >= 70) {
                $forecast['by_probability']['high']['count']++;
                $forecast['by_probability']['high']['value'] += $opportunity->amount;
            } elseif ($probability >= 30) {
                $forecast['by_probability']['medium']['count']++;
                $forecast['by_probability']['medium']['value'] += $opportunity->amount;
            } else {
                $forecast['by_probability']['low']['count']++;
                $forecast['by_probability']['low']['value'] += $opportunity->amount;
            }
        }

        return $forecast;
    }
}