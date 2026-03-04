<?php

namespace App\Http\Controllers\Api\V1\CRM;

use App\Domain\CRM\Contracts\PipelineServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\CreatePipelineRequest;
use App\Http\Requests\CRM\UpdatePipelineRequest;
use App\Http\Resources\CRM\PipelineResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PipelineController extends Controller
{
    public function __construct(
        private readonly PipelineServiceInterface $pipelineService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $companyId = $request->user()->currentCompany->id;
        
        $pipelines = $this->pipelineService->getForCompany($companyId);
        
        return PipelineResource::collection($pipelines);
    }

    public function active(Request $request): AnonymousResourceCollection
    {
        $companyId = $request->user()->currentCompany->id;
        
        $pipelines = $this->pipelineService->getActive($companyId);
        
        return PipelineResource::collection($pipelines);
    }

    public function getDefault(Request $request): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        $pipeline = $this->pipelineService->getDefault($companyId);
        
        if (!$pipeline) {
            return response()->json([
                'message' => __('crm.no_default_pipeline_found')
            ], 404);
        }
        
        return response()->json([
            'data' => new PipelineResource($pipeline)
        ]);
    }

    public function store(CreatePipelineRequest $request): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        $userId = $request->user()->id;
        
        $pipeline = $this->pipelineService->create($request->validated(), $companyId, $userId);
        
        return response()->json([
            'message' => __('crm.pipeline_created_successfully'),
            'data' => new PipelineResource($pipeline)
        ], 201);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $pipeline = $this->pipelineService->findByUuid($uuid, $companyId);
        
        if (!$pipeline) {
            return response()->json([
                'message' => __('crm.pipeline_not_found')
            ], 404);
        }
        
        return response()->json([
            'data' => new PipelineResource($pipeline->load(['stages', 'opportunities']))
        ]);
    }

    public function update(UpdatePipelineRequest $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $pipeline = $this->pipelineService->findByUuid($uuid, $companyId);
        
        if (!$pipeline) {
            return response()->json([
                'message' => __('crm.pipeline_not_found')
            ], 404);
        }
        
        $pipeline = $this->pipelineService->update($pipeline, $request->validated());
        
        return response()->json([
            'message' => __('crm.pipeline_updated_successfully'),
            'data' => new PipelineResource($pipeline)
        ]);
    }

    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $pipeline = $this->pipelineService->findByUuid($uuid, $companyId);
        
        if (!$pipeline) {
            return response()->json([
                'message' => __('crm.pipeline_not_found')
            ], 404);
        }
        
        try {
            $this->pipelineService->delete($pipeline);
            
            return response()->json([
                'message' => __('crm.pipeline_deleted_successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('crm.cannot_delete_pipeline_with_opportunities')
            ], 422);
        }
    }

    public function setAsDefault(Request $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $pipeline = $this->pipelineService->findByUuid($uuid, $companyId);
        
        if (!$pipeline) {
            return response()->json([
                'message' => __('crm.pipeline_not_found')
            ], 404);
        }
        
        $pipeline = $this->pipelineService->setAsDefault($pipeline);
        
        return response()->json([
            'message' => __('crm.pipeline_set_as_default_successfully'),
            'data' => new PipelineResource($pipeline)
        ]);
    }

    public function activate(Request $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $pipeline = $this->pipelineService->findByUuid($uuid, $companyId);
        
        if (!$pipeline) {
            return response()->json([
                'message' => __('crm.pipeline_not_found')
            ], 404);
        }
        
        $pipeline = $this->pipelineService->activate($pipeline);
        
        return response()->json([
            'message' => __('crm.pipeline_activated_successfully'),
            'data' => new PipelineResource($pipeline)
        ]);
    }

    public function deactivate(Request $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $pipeline = $this->pipelineService->findByUuid($uuid, $companyId);
        
        if (!$pipeline) {
            return response()->json([
                'message' => __('crm.pipeline_not_found')
            ], 404);
        }
        
        try {
            $pipeline = $this->pipelineService->deactivate($pipeline);
            
            return response()->json([
                'message' => __('crm.pipeline_deactivated_successfully'),
                'data' => new PipelineResource($pipeline)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('crm.cannot_deactivate_default_pipeline')
            ], 422);
        }
    }

    public function duplicate(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        $userId = $request->user()->id;
        
        $pipeline = $this->pipelineService->findByUuid($uuid, $companyId);
        
        if (!$pipeline) {
            return response()->json([
                'message' => __('crm.pipeline_not_found')
            ], 404);
        }
        
        $newPipeline = $this->pipelineService->duplicatePipeline($pipeline, $request->name, $userId);
        $newPipeline->load('stages');
        
        return response()->json([
            'message' => __('crm.pipeline_created_successfully'),
            'data' => new PipelineResource($newPipeline)
        ], 201);
    }

    public function metrics(Request $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $pipeline = $this->pipelineService->findByUuid($uuid, $companyId);
        
        if (!$pipeline) {
            return response()->json([
                'message' => __('crm.pipeline_not_found')
            ], 404);
        }
        
        $metrics = $this->pipelineService->getPipelineMetrics($pipeline);
        
        return response()->json([
            'data' => $metrics
        ]);
    }

    public function createStage(Request $request, string $uuid): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'probability' => 'required|integer|min:0|max:100',
            'is_closed_won' => 'boolean',
            'is_closed_lost' => 'boolean',
            'requires_reason' => 'boolean',
            'max_days_in_stage' => 'nullable|integer|min:1',
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        $userId = $request->user()->id;
        
        $pipeline = $this->pipelineService->findByUuid($uuid, $companyId);
        
        if (!$pipeline) {
            return response()->json([
                'message' => __('crm.pipeline_not_found')
            ], 404);
        }
        
        $stage = $this->pipelineService->createStage($pipeline, $validatedData, $userId);
        
        return response()->json([
            'message' => __('crm.stage_created_successfully'),
            'data' => new \App\Http\Resources\CRM\PipelineStageResource($stage)
        ], 201);
    }

    public function updateStage(Request $request, string $uuid, string $stageUuid): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'probability' => 'sometimes|required|integer|min:0|max:100',
            'is_active' => 'boolean',
            'is_closed_won' => 'boolean',
            'is_closed_lost' => 'boolean',
            'requires_reason' => 'boolean',
            'max_days_in_stage' => 'nullable|integer|min:1',
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        
        $pipeline = $this->pipelineService->findByUuid($uuid, $companyId);
        
        if (!$pipeline) {
            return response()->json([
                'message' => __('crm.pipeline_not_found')
            ], 404);
        }
        
        $stage = $pipeline->stages()->where('uuid', $stageUuid)->first();
        
        if (!$stage) {
            return response()->json([
                'message' => __('crm.stage_not_found')
            ], 404);
        }
        
        $stage = $this->pipelineService->updateStage($stage, $validatedData);
        
        return response()->json([
            'message' => __('crm.stage_updated_successfully'),
            'data' => new \App\Http\Resources\CRM\PipelineStageResource($stage)
        ]);
    }

    public function deleteStage(Request $request, string $uuid, string $stageUuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $pipeline = $this->pipelineService->findByUuid($uuid, $companyId);
        
        if (!$pipeline) {
            return response()->json([
                'message' => __('crm.pipeline_not_found')
            ], 404);
        }
        
        $stage = $pipeline->stages()->where('uuid', $stageUuid)->first();
        
        if (!$stage) {
            return response()->json([
                'message' => __('crm.stage_not_found')
            ], 404);
        }
        
        try {
            $this->pipelineService->deleteStage($stage);
            
            return response()->json([
                'message' => __('crm.stage_deleted_successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('crm.cannot_delete_stage_with_opportunities')
            ], 422);
        }
    }

    public function reorderStages(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'stage_order' => 'required|array',
            'stage_order.*' => 'integer|exists:pipeline_stages,id'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        
        $pipeline = $this->pipelineService->findByUuid($uuid, $companyId);
        
        if (!$pipeline) {
            return response()->json([
                'message' => __('crm.pipeline_not_found')
            ], 404);
        }
        
        $this->pipelineService->reorderStages($pipeline, $request->stage_order);
        
        return response()->json([
            'message' => __('crm.stages_reordered_successfully')
        ]);
    }
}