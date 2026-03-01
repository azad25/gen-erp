<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\LeaveRequest;
use App\Services\HRService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Leave Requests",
 *     description="Leave request management"
 * )
 * REST API v1 controller for Leave Request operations.
 */
class LeaveRequestController extends BaseApiController
{
    public function __construct(
        private HRService $hrService
    ) {}

    /**
     * @OA\Get(
     *     path="/leave-requests",
     *     summary="List all leave requests",
     *     tags={"Leave Requests"},
     *     @OA\Parameter(name="employee_id", in="query", description="Employee ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", description="Status", @OA\Schema(type="string")),
     *     @OA\Parameter(name="leave_type_id", in="query", description="Leave Type ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/LeaveRequest")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $leaveRequests = LeaveRequest::query()
            ->when($request->get('employee_id'), fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->get('leave_type_id'), fn ($q, $id) => $q->where('leave_type_id', $id))
            ->with(['employee', 'leaveType'])
            ->orderBy('start_date', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($leaveRequests);
    }

    /**
     * @OA\Get(
     *     path="/leave-requests/{id}",
     *     summary="Get a specific leave request",
     *     tags={"Leave Requests"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Leave Request ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/LeaveRequest")
     *         )
     *     )
     * )
     */
    public function show(LeaveRequest $leaveRequest): JsonResponse
    {
        $leaveRequest->load(['employee', 'leaveType']);

        return $this->success($leaveRequest);
    }

    /**
     * @OA\Post(
     *     path="/leave-requests",
     *     summary="Create a new leave request",
     *     tags={"Leave Requests"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="employee_id", type="integer"),
     *             @OA\Property(property="leave_type_id", type="integer"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="reason", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Leave request created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/LeaveRequest"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $validated['company_id'] = activeCompany()?->id;
        $validated['status'] = 'pending';

        $leaveRequest = $this->hrService->requestLeave($validated);

        return $this->success($leaveRequest->load(['employee', 'leaveType']), 'Leave request created', 201);
    }

    public function update(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after:start_date'],
            'reason' => ['sometimes', 'string', 'max:1000'],
        ]);

        $leaveRequest->update($validated);

        return $this->success($leaveRequest->fresh(), 'Leave request updated');
    }

    public function destroy(LeaveRequest $leaveRequest): JsonResponse
    {
        $leaveRequest->delete();

        return $this->success(null, 'Leave request deleted');
    }

    /**
     * @OA\Post(
     *     path="/leave-requests/{leaveRequest}/approve",
     *     summary="Approve a leave request",
     *     tags={"Leave Requests"},
     *     @OA\Parameter(name="leaveRequest", in="path", required=true, description="Leave Request ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Leave request approved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function approve(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $this->hrService->approveLeave($leaveRequest, $validated['notes'] ?? null);

        return $this->success($result, 'Leave request approved');
    }

    /**
     * @OA\Post(
     *     path="/leave-requests/{leaveRequest}/reject",
     *     summary="Reject a leave request",
     *     tags={"Leave Requests"},
     *     @OA\Parameter(name="leaveRequest", in="path", required=true, description="Leave Request ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Leave request rejected",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function reject(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $result = $this->hrService->rejectLeave($leaveRequest, $validated['reason']);

        return $this->success($result, 'Leave request rejected');
    }
}
