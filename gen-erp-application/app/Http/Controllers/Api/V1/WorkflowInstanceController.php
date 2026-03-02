<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\WorkflowInstance;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Workflow Instances",
 *     description="Workflow instance management"
 * )
 * REST API v1 controller for Workflow Instance operations.
 */
class WorkflowInstanceController extends BaseApiController
{
    public function __construct(
        private WorkflowService $workflowService
    ) {}

    /**
     * @OA\Get(
     *     path="/workflow-instances",
     *     summary="List all workflow instances",
     *     tags={"Workflow Instances"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Instance status", @OA\Schema(type="string")),
     *     @OA\Parameter(name="workflow_type", in="query", description="Workflow type", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/WorkflowInstance")
     *             ),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $instances = WorkflowInstance::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('search'), fn ($q, $s) => $q->where('document_number', 'LIKE', "%{$s}%"))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->get('workflow_type'), fn ($q, $s) => $q->where('workflow_type', $s))
            ->with(['documentable', 'currentStep'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($instances);
    }

    /**
     * @OA\Get(
     *     path="/workflow-instances/{id}",
     *     summary="Get a specific workflow instance",
     *     tags={"Workflow Instances"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Workflow Instance ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/WorkflowInstance")
     *         )
     *     )
     * )
     */
    public function show(WorkflowInstance $workflowInstance): JsonResponse
    {
        $workflowInstance->load(['documentable', 'currentStep', 'steps']);

        return $this->success($workflowInstance);
    }

    /**
     * @OA\Post(
     *     path="/workflow-instances",
     *     summary="Create a new workflow instance",
     *     tags={"Workflow Instances"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="document_type", type="string"),
     *             @OA\Property(property="document_id", type="integer"),
     *             @OA\Property(property="workflow_type", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Workflow instance created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/WorkflowInstance"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type' => ['required', 'string'],
            'document_id' => ['required', 'integer'],
            'workflow_type' => ['required', 'string'],
        ]);

        $validated['company_id'] = activeCompany()->id;

        $instance = $this->workflowService->initialise($validated);

        return $this->success($instance->load(['documentable', 'currentStep']), 'Workflow instance created', 201);
    }

    /**
     * @OA\Post(
     *     path="/workflow-instances/{workflowInstance}/transition",
     *     summary="Transition workflow instance",
     *     tags={"Workflow Instances"},
     *     @OA\Parameter(name="workflowInstance", in="path", required=true, description="Workflow Instance ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="transition", type="string"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Workflow transition completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/WorkflowInstance"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function transition(Request $request, WorkflowInstance $workflowInstance): JsonResponse
    {
        $validated = $request->validate([
            'transition' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $result = $this->workflowService->transition($workflowInstance, $validated['transition'], $validated['notes']);

        return $this->success($result, 'Workflow transition completed');
    }
}
