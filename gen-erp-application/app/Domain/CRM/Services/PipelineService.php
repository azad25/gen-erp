<?php

namespace App\Domain\CRM\Services;

use App\Domain\CRM\Contracts\PipelineServiceInterface;
use App\Domain\CRM\Models\Pipeline;
use App\Domain\CRM\Models\Opportunity;
use App\Domain\CRM\Models\PipelineStage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PipelineService implements PipelineServiceInterface
{
    public function create(array $data, int $companyId, int $createdBy): Pipeline
    {
        return DB::transaction(function () use ($data, $companyId, $createdBy) {
            $pipelineData = array_merge($data, [
                'company_id' => $companyId,
                'created_by' => $createdBy,
                'sort_order' => $this->getNextSortOrder($companyId),
            ]);

            $pipeline = Pipeline::create($pipelineData);

            // Create default stages if requested
            if ($data['create_default_stages'] ?? true) {
                $pipeline->createDefaultStages();
            }

            return $pipeline->fresh();
        });
    }

    public function update(Pipeline $pipeline, array $data): Pipeline
    {
        $pipeline->update($data);
        return $pipeline->fresh();
    }

    public function delete(Pipeline $pipeline): bool
    {
        return DB::transaction(function () use ($pipeline) {
            // Check if pipeline has opportunities
            if ($pipeline->opportunities()->count() > 0) {
                throw new \Exception('Cannot delete pipeline with existing opportunities');
            }

            // Delete stages first
            $pipeline->stages()->delete();

            return $pipeline->delete();
        });
    }

    public function findById(int $id, int $companyId): ?Pipeline
    {
        return Pipeline::forCompany($companyId)->find($id);
    }

    public function findByUuid(string $uuid, int $companyId): ?Pipeline
    {
        return Pipeline::forCompany($companyId)->where('uuid', $uuid)->first();
    }

    public function getForCompany(int $companyId): Collection
    {
        return Pipeline::forCompany($companyId)
            ->with(['stages' => function ($query) {
                $query->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();
    }

    public function getActive(int $companyId): Collection
    {
        return Pipeline::forCompany($companyId)
            ->active()
            ->with(['stages' => function ($query) {
                $query->active()->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();
    }

    public function getDefault(int $companyId): ?Pipeline
    {
        return Pipeline::forCompany($companyId)->default()->first();
    }

    public function setAsDefault(Pipeline $pipeline): Pipeline
    {
        return DB::transaction(function () use ($pipeline) {
            // Remove default from other pipelines
            Pipeline::forCompany($pipeline->company_id)
                ->where('id', '!=', $pipeline->id)
                ->update(['is_default' => false]);

            // Set this pipeline as default
            $pipeline->update(['is_default' => true, 'is_active' => true]);

            return $pipeline->fresh();
        });
    }

    public function activate(Pipeline $pipeline): Pipeline
    {
        $pipeline->update(['is_active' => true]);
        return $pipeline->fresh();
    }

    public function deactivate(Pipeline $pipeline): Pipeline
    {
        return DB::transaction(function () use ($pipeline) {
            // Cannot deactivate if it's the default pipeline
            if ($pipeline->is_default) {
                throw new \Exception('Cannot deactivate the default pipeline');
            }

            $pipeline->update(['is_active' => false]);
            return $pipeline->fresh();
        });
    }

    public function createStage(Pipeline $pipeline, array $data, int $createdBy): PipelineStage
    {
        $stageData = array_merge($data, [
            'company_id' => $pipeline->company_id,
            'pipeline_id' => $pipeline->id,
            'created_by' => $createdBy,
            'sort_order' => $this->getNextStageSortOrder($pipeline),
        ]);

        return PipelineStage::create($stageData);
    }

    public function updateStage(PipelineStage $stage, array $data): PipelineStage
    {
        $stage->update($data);
        return $stage->fresh();
    }

    public function deleteStage(PipelineStage $stage): bool
    {
        return DB::transaction(function () use ($stage) {
            // Check if stage has opportunities
            if ($stage->opportunities()->count() > 0) {
                throw new \Exception('Cannot delete stage with existing opportunities');
            }

            return $stage->delete();
        });
    }

    public function reorderStages(Pipeline $pipeline, array $stageOrder): void
    {
        DB::transaction(function () use ($pipeline, $stageOrder) {
            // First, set all stages to a temporary high sort_order to avoid unique constraint violations
            PipelineStage::where('pipeline_id', $pipeline->id)
                ->update(['sort_order' => DB::raw('sort_order + 1000')]);
            
            // Then update with the new order
            foreach ($stageOrder as $index => $stageId) {
                PipelineStage::where('id', $stageId)
                    ->where('pipeline_id', $pipeline->id)
                    ->update(['sort_order' => $index + 1]);
            }
        });
    }

    public function duplicatePipeline(Pipeline $pipeline, string $newName, int $createdBy): Pipeline
    {
        return DB::transaction(function () use ($pipeline, $newName, $createdBy) {
            // Create new pipeline
            $newPipeline = Pipeline::create([
                'company_id' => $pipeline->company_id,
                'created_by' => $createdBy,
                'name' => $newName,
                'description' => $pipeline->description,
                'color' => $pipeline->color,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => $this->getNextSortOrder($pipeline->company_id),
                'settings' => $pipeline->settings,
                'auto_move_stages' => $pipeline->auto_move_stages,
                'default_probability' => $pipeline->default_probability,
            ]);

            // Duplicate stages
            foreach ($pipeline->stages as $stage) {
                PipelineStage::create([
                    'company_id' => $newPipeline->company_id,
                    'pipeline_id' => $newPipeline->id,
                    'created_by' => $createdBy,
                    'name' => $stage->name,
                    'description' => $stage->description,
                    'color' => $stage->color,
                    'sort_order' => $stage->sort_order,
                    'is_active' => $stage->is_active,
                    'probability' => $stage->probability,
                    'is_closed_won' => $stage->is_closed_won,
                    'is_closed_lost' => $stage->is_closed_lost,
                    'requires_reason' => $stage->requires_reason,
                    'entry_actions' => $stage->entry_actions,
                    'exit_actions' => $stage->exit_actions,
                    'max_days_in_stage' => $stage->max_days_in_stage,
                ]);
            }

            return $newPipeline->fresh();
        });
    }

    public function getPipelineMetrics(Pipeline $pipeline): array
    {
        // Helper function to create fresh base query
        $createBaseQuery = function() use ($pipeline) {
            return Opportunity::where('pipeline_id', $pipeline->id);
        };
        
        $metrics = [
            'total_opportunities' => $createBaseQuery()->count(),
            'total_value' => $createBaseQuery()->sum('amount'),
            'won_opportunities' => $createBaseQuery()->won()->count(),
            'won_value' => $createBaseQuery()->won()->sum('amount'),
            'lost_opportunities' => $createBaseQuery()->lost()->count(),
            'lost_value' => $createBaseQuery()->lost()->sum('amount'),
            'open_opportunities' => $createBaseQuery()->open()->count(),
            'open_value' => $createBaseQuery()->open()->sum('amount'),
            'weighted_value' => $createBaseQuery()->open()->get()->sum('weighted_value'),
            'conversion_rate' => 0,
            'average_deal_size' => 0,
            'average_sales_cycle' => 0,
            'stages' => [],
        ];

        // Calculate rates and averages
        if ($metrics['total_opportunities'] > 0) {
            $metrics['conversion_rate'] = round(($metrics['won_opportunities'] / $metrics['total_opportunities']) * 100, 2);
            $metrics['average_deal_size'] = round($metrics['total_value'] / $metrics['total_opportunities'], 2);
        }

        // Calculate average sales cycle for won opportunities
        $wonOpportunities = $createBaseQuery()->won()->whereNotNull('won_at')->get();
        if ($wonOpportunities->count() > 0) {
            $totalDays = $wonOpportunities->sum(function ($opportunity) {
                return $opportunity->created_at->diffInDays($opportunity->won_at);
            });
            $metrics['average_sales_cycle'] = round($totalDays / $wonOpportunities->count(), 1);
        }

        // Stage metrics
        foreach ($pipeline->stages as $stage) {
            $stageOpportunities = Opportunity::where('stage_id', $stage->id);
            $metrics['stages'][] = [
                'id' => $stage->id,
                'name' => $stage->name,
                'opportunities_count' => $stageOpportunities->count(),
                'total_value' => $stageOpportunities->sum('amount'),
                'conversion_rate' => $stage->conversion_rate,
                'average_days' => $stage->average_days,
            ];
        }

        return $metrics;
    }

    private function getNextSortOrder(int $companyId): int
    {
        return Pipeline::forCompany($companyId)->max('sort_order') + 1;
    }

    private function getNextStageSortOrder(Pipeline $pipeline): int
    {
        return $pipeline->stages()->max('sort_order') + 1;
    }
}