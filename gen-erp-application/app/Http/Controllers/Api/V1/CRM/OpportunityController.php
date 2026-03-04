<?php

namespace App\Http\Controllers\Api\V1\CRM;

use App\Domain\CRM\Contracts\OpportunityServiceInterface;
use App\Domain\CRM\DTOs\OpportunityData;
use App\Domain\CRM\Models\PipelineStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\CreateOpportunityRequest;
use App\Http\Requests\CRM\UpdateOpportunityRequest;
use App\Http\Resources\CRM\OpportunityResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OpportunityController extends Controller
{
    public function __construct(
        private readonly OpportunityServiceInterface $opportunityService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $companyId = $request->user()->currentCompany->id;
        
        $filters = $request->only([
            'status', 'pipeline_id', 'stage_id', 'assigned_to', 'expected_close_from',
            'expected_close_to', 'min_amount', 'max_amount', 'search', 'sort_by', 'sort_order'
        ]);
        
        $perPage = $request->get('per_page', 15);
        
        $opportunities = $this->opportunityService->getForCompany($companyId, $filters, $perPage);
        
        return OpportunityResource::collection($opportunities);
    }

    public function store(CreateOpportunityRequest $request): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        $userId = $request->user()->id;
        
        $opportunityData = OpportunityData::fromArray($request->validated());
        $opportunity = $this->opportunityService->create($opportunityData, $companyId, $userId);
        
        return response()->json([
            'message' => __('crm.opportunity_created_successfully'),
            'data' => new OpportunityResource($opportunity)
        ], 201);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $opportunity = $this->opportunityService->findByUuid($uuid, $companyId);
        
        if (!$opportunity) {
            return response()->json([
                'message' => __('crm.opportunity_not_found')
            ], 404);
        }
        
        return response()->json([
            'data' => new OpportunityResource($opportunity->load(['pipeline', 'stage', 'lead', 'customer', 'activities']))
        ]);
    }

    public function update(UpdateOpportunityRequest $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $opportunity = $this->opportunityService->findByUuid($uuid, $companyId);
        
        if (!$opportunity) {
            return response()->json([
                'message' => __('crm.opportunity_not_found')
            ], 404);
        }
        
        $opportunityData = OpportunityData::fromArrayForUpdate($request->validated(), $opportunity);
        $opportunity = $this->opportunityService->update($opportunity, $opportunityData);
        
        return response()->json([
            'message' => __('crm.opportunity_updated_successfully'),
            'data' => new OpportunityResource($opportunity)
        ]);
    }

    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $opportunity = $this->opportunityService->findByUuid($uuid, $companyId);
        
        if (!$opportunity) {
            return response()->json([
                'message' => __('crm.opportunity_not_found')
            ], 404);
        }
        
        $this->opportunityService->delete($opportunity);
        
        return response()->json([
            'message' => __('crm.opportunity_deleted_successfully')
        ]);
    }

    public function moveToStage(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'stage_id' => 'required|integer|exists:pipeline_stages,id',
            'reason' => 'nullable|string|max:500'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        $opportunity = $this->opportunityService->findByUuid($uuid, $companyId);
        
        if (!$opportunity) {
            return response()->json([
                'message' => __('crm.opportunity_not_found')
            ], 404);
        }
        
        $stage = PipelineStage::forCompany($companyId)->find($request->stage_id);
        
        if (!$stage) {
            return response()->json([
                'message' => __('crm.stage_not_found')
            ], 404);
        }
        
        $opportunity = $this->opportunityService->moveToStage($opportunity, $stage, $request->reason);
        
        return response()->json([
            'message' => __('crm.opportunity_moved_successfully'),
            'data' => new OpportunityResource($opportunity)
        ]);
    }

    public function markAsWon(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        $opportunity = $this->opportunityService->findByUuid($uuid, $companyId);
        
        if (!$opportunity) {
            return response()->json([
                'message' => __('crm.opportunity_not_found')
            ], 404);
        }
        
        $opportunity = $this->opportunityService->markAsWon($opportunity, $request->reason);
        
        return response()->json([
            'message' => __('crm.opportunity_won_successfully'),
            'data' => new OpportunityResource($opportunity)
        ]);
    }

    public function markAsLost(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        $opportunity = $this->opportunityService->findByUuid($uuid, $companyId);
        
        if (!$opportunity) {
            return response()->json([
                'message' => __('crm.opportunity_not_found')
            ], 404);
        }
        
        $opportunity = $this->opportunityService->markAsLost($opportunity, $request->reason);
        
        return response()->json([
            'message' => __('crm.opportunity_lost_successfully'),
            'data' => new OpportunityResource($opportunity)
        ]);
    }

    public function assign(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'assigned_to' => 'required|integer|exists:users,id'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        $opportunity = $this->opportunityService->findByUuid($uuid, $companyId);
        
        if (!$opportunity) {
            return response()->json([
                'message' => __('crm.opportunity_not_found')
            ], 404);
        }
        
        $opportunity = $this->opportunityService->assignTo($opportunity, $request->assigned_to);
        
        return response()->json([
            'message' => __('crm.opportunity_assigned_successfully'),
            'data' => new OpportunityResource($opportunity)
        ]);
    }

    public function updateProbability(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'probability' => 'required|integer|min:0|max:100'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        $opportunity = $this->opportunityService->findByUuid($uuid, $companyId);
        
        if (!$opportunity) {
            return response()->json([
                'message' => __('crm.opportunity_not_found')
            ], 404);
        }
        
        $opportunity = $this->opportunityService->updateProbability($opportunity, $request->probability);
        
        return response()->json([
            'message' => __('crm.opportunity_updated_successfully'),
            'data' => new OpportunityResource($opportunity)
        ]);
    }

    public function bulkMoveToStage(Request $request): JsonResponse
    {
        $request->validate([
            'opportunity_ids' => 'required|array',
            'opportunity_ids.*' => 'integer|exists:opportunities,id',
            'stage_id' => 'required|integer|exists:pipeline_stages,id'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        
        $updated = $this->opportunityService->bulkMoveToStage(
            $request->opportunity_ids,
            $request->stage_id,
            $companyId
        );
        
        return response()->json([
            'message' => __('crm.opportunities_moved_successfully', ['count' => $updated])
        ]);
    }

    public function bulkAssign(Request $request): JsonResponse
    {
        $request->validate([
            'opportunity_ids' => 'required|array',
            'opportunity_ids.*' => 'integer|exists:opportunities,id',
            'assigned_to' => 'required|integer|exists:users,id'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        
        $updated = $this->opportunityService->bulkAssign(
            $request->opportunity_ids,
            $request->assigned_to,
            $companyId
        );
        
        return response()->json([
            'message' => __('crm.opportunities_assigned_successfully', ['count' => $updated])
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $filters = $request->only(['date_from', 'date_to']);
        $statistics = $this->opportunityService->getOpportunityStatistics($companyId, $filters);
        
        return response()->json([
            'data' => $statistics
        ]);
    }

    public function forecast(Request $request): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $filters = $request->only(['pipeline_id', 'assigned_to']);
        $forecast = $this->opportunityService->getForecast($companyId, $filters);
        
        return response()->json([
            'data' => $forecast
        ]);
    }

    public function pipelineView(Request $request, int $pipelineId): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $opportunities = $this->opportunityService->getForPipeline($pipelineId, $companyId);
        
        return response()->json([
            'data' => OpportunityResource::collection($opportunities)
        ]);
    }

    public function stageView(Request $request, int $stageId): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $opportunities = $this->opportunityService->getForStage($stageId, $companyId);
        
        return response()->json([
            'data' => OpportunityResource::collection($opportunities)
        ]);
    }
}