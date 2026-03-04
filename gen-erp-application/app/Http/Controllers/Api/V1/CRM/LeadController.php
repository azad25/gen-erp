<?php

namespace App\Http\Controllers\Api\V1\CRM;

use App\Domain\CRM\Contracts\LeadServiceInterface;
use App\Domain\CRM\DTOs\LeadData;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\CreateLeadRequest;
use App\Http\Requests\CRM\UpdateLeadRequest;
use App\Http\Resources\CRM\LeadResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeadController extends Controller
{
    public function __construct(
        private readonly LeadServiceInterface $leadService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $companyId = $request->user()->currentCompany->id;
        
        $filters = $request->only([
            'status', 'assigned_to', 'source', 'min_score', 'search',
            'date_from', 'date_to', 'sort_by', 'sort_order'
        ]);
        
        $perPage = $request->get('per_page', 15);
        
        $leads = $this->leadService->getForCompany($companyId, $filters, $perPage);
        
        return LeadResource::collection($leads);
    }

    public function store(CreateLeadRequest $request): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        $userId = $request->user()->id;
        
        $leadData = LeadData::fromArray($request->validated());
        $lead = $this->leadService->create($leadData, $companyId, $userId);
        
        return response()->json([
            'message' => __('crm.lead_created_successfully'),
            'data' => new LeadResource($lead)
        ], 201);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $lead = $this->leadService->findByUuid($uuid, $companyId);
        
        if (!$lead) {
            return response()->json([
                'message' => __('crm.lead_not_found')
            ], 404);
        }
        
        return response()->json([
            'data' => new LeadResource($lead->load(['notes', 'tags', 'activities', 'opportunities', 'createdBy', 'assignedTo']))
        ]);
    }

    public function update(UpdateLeadRequest $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $lead = $this->leadService->findByUuid($uuid, $companyId);
        
        if (!$lead) {
            return response()->json([
                'message' => __('crm.lead_not_found')
            ], 404);
        }
        
        $leadData = LeadData::fromArray($request->validated());
        $lead = $this->leadService->update($lead, $leadData);
        
        return response()->json([
            'message' => __('crm.lead_updated_successfully'),
            'data' => new LeadResource($lead)
        ]);
    }

    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $lead = $this->leadService->findByUuid($uuid, $companyId);
        
        if (!$lead) {
            return response()->json([
                'message' => __('crm.lead_not_found')
            ], 404);
        }
        
        $this->leadService->delete($lead);
        
        return response()->json([
            'message' => __('crm.lead_deleted_successfully')
        ]);
    }

    public function assign(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'assigned_to' => 'required|integer|exists:users,id'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        $lead = $this->leadService->findByUuid($uuid, $companyId);
        
        if (!$lead) {
            return response()->json([
                'message' => __('crm.lead_not_found')
            ], 404);
        }
        
        $lead = $this->leadService->assignTo($lead, $request->assigned_to);
        
        return response()->json([
            'message' => __('crm.lead_assigned_successfully'),
            'data' => new LeadResource($lead)
        ]);
    }

    public function updateScore(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'score' => 'required|integer|min:0|max:100'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        $lead = $this->leadService->findByUuid($uuid, $companyId);
        
        if (!$lead) {
            return response()->json([
                'message' => __('crm.lead_not_found')
            ], 404);
        }
        
        $lead = $this->leadService->updateScore($lead, $request->score);
        
        return response()->json([
            'message' => __('crm.lead_score_updated_successfully'),
            'data' => new LeadResource($lead)
        ]);
    }

    public function qualify(Request $request, string $uuid): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        $lead = $this->leadService->findByUuid($uuid, $companyId);
        
        if (!$lead) {
            return response()->json([
                'message' => __('crm.lead_not_found')
            ], 404);
        }
        
        $lead = $this->leadService->qualify($lead);
        
        return response()->json([
            'message' => __('crm.lead_qualified_successfully'),
            'data' => new LeadResource($lead)
        ]);
    }

    public function addNote(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'content' => 'required|string',
            'title' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:general,call_log,meeting_notes,follow_up',
            'is_private' => 'boolean',
            'is_pinned' => 'boolean'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        $lead = $this->leadService->findByUuid($uuid, $companyId);
        
        if (!$lead) {
            return response()->json([
                'message' => __('crm.lead_not_found')
            ], 404);
        }
        
        $this->leadService->addNote(
            $lead,
            $request->content,
            $request->user()->id,
            $request->only(['title', 'type', 'is_private', 'is_pinned'])
        );
        
        return response()->json([
            'message' => __('crm.note_added_successfully')
        ]);
    }

    public function addTag(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'tag_id' => 'required|integer|exists:lead_tags,id'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        $lead = $this->leadService->findByUuid($uuid, $companyId);
        
        if (!$lead) {
            return response()->json([
                'message' => __('crm.lead_not_found')
            ], 404);
        }
        
        $this->leadService->addTag($lead, $request->tag_id, $request->user()->id);
        
        return response()->json([
            'message' => __('crm.tag_added_successfully')
        ]);
    }

    public function removeTag(Request $request, string $uuid, int $tagId): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        $lead = $this->leadService->findByUuid($uuid, $companyId);
        
        if (!$lead) {
            return response()->json([
                'message' => __('crm.lead_not_found')
            ], 404);
        }
        
        $this->leadService->removeTag($lead, $tagId);
        
        return response()->json([
            'message' => __('crm.tag_removed_successfully')
        ]);
    }

    public function bulkAssign(Request $request): JsonResponse
    {
        $request->validate([
            'lead_ids' => 'required|array',
            'lead_ids.*' => 'integer|exists:leads,id',
            'assigned_to' => 'required|integer|exists:users,id'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        
        $updated = $this->leadService->bulkAssign(
            $request->lead_ids,
            $request->assigned_to,
            $companyId
        );
        
        return response()->json([
            'message' => __('crm.leads_assigned_successfully', ['count' => $updated])
        ]);
    }

    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'lead_ids' => 'required|array',
            'lead_ids.*' => 'integer|exists:leads,id',
            'status' => 'required|string|in:new,contacted,qualified,unqualified,converted'
        ]);
        
        $companyId = $request->user()->currentCompany->id;
        
        $updated = $this->leadService->bulkUpdateStatus(
            $request->lead_ids,
            $request->status,
            $companyId
        );
        
        return response()->json([
            'message' => __('crm.leads_status_updated_successfully', ['count' => $updated])
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $filters = $request->only(['date_from', 'date_to']);
        $statistics = $this->leadService->getLeadStatistics($companyId, $filters);
        
        return response()->json([
            'data' => $statistics
        ]);
    }

    public function myLeads(Request $request): AnonymousResourceCollection
    {
        $companyId = $request->user()->currentCompany->id;
        $userId = $request->user()->id;
        
        $filters = $request->only(['status', 'source']);
        $leads = $this->leadService->getAssignedTo($userId, $companyId, $filters);
        
        // Load the assignedTo relationship for the resource
        $leads->load('assignedTo');
        
        return LeadResource::collection($leads);
    }
}