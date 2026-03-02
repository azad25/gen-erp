<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ApprovalRequest;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Approval Requests",
 *     description="Approval request management"
 * )
 * REST API v1 controller for Approval Request operations.
 */
class ApprovalRequestController extends BaseApiController
{
    public function __construct(
        private WorkflowService $workflowService
    ) {}

    /**
     * @OA\Get(
     *     path="/approval-requests",
     *     summary="List all approval requests",
     *     tags={"Approval Requests"},
     *     @OA\Parameter(name="status", in="query", description="Status", @OA\Schema(type="string")),
     *     @OA\Parameter(name="user_id", in="query", description="User ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/ApprovalRequest")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $requests = ApprovalRequest::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->get('user_id'), fn ($q, $id) => $q->where('user_id', $id))
            ->with(['user', 'workflowInstance', 'step'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($requests);
    }

    /**
     * @OA\Get(
     *     path="/approval-requests/{id}",
     *     summary="Get a specific approval request",
     *     tags={"Approval Requests"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Approval Request ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/ApprovalRequest")
     *         )
     *     )
     * )
     */
    public function show(ApprovalRequest $approvalRequest): JsonResponse
    {
        $approvalRequest->load(['user', 'workflowInstance', 'step']);

        return $this->success($approvalRequest);
    }

    /**
     * @OA\Post(
     *     path="/approval-requests/{approvalRequest}/approve",
     *     summary="Approve an approval request",
     *     tags={"Approval Requests"},
     *     @OA\Parameter(name="approvalRequest", in="path", required=true, description="Approval Request ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Request approved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function approve(Request $request, ApprovalRequest $approvalRequest): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $result = $this->workflowService->respondToApproval($approvalRequest, 'approved', $validated['notes'] ?? null);

        return $this->success($result, 'Request approved');
    }

    /**
     * @OA\Post(
     *     path="/approval-requests/{approvalRequest}/reject",
     *     summary="Reject an approval request",
     *     tags={"Approval Requests"},
     *     @OA\Parameter(name="approvalRequest", in="path", required=true, description="Approval Request ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Request rejected",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function reject(Request $request, ApprovalRequest $approvalRequest): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $result = $this->workflowService->respondToApproval($approvalRequest, 'rejected', $validated['reason']);

        return $this->success($result, 'Request rejected');
    }
}
