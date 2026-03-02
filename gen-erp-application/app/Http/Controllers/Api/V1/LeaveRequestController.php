<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\HRService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

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
        private readonly HRService $hrService
    ) {}

    /**
     * @OA\Get(
     *     path="/leave-requests",
     *     summary="List all leave requests",
     *     tags={"Leave Requests"},
     *     @OA\Parameter(name="employee_id", in="query", description="Employee ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", description="Status", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $leaveRequests = LeaveRequest::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('employee_id'), fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
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
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function show(LeaveRequest $leaveRequest): JsonResponse
    {
        $leaveRequest->load(['employee', 'leaveType', 'approver']);

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
     *     @OA\Response(response=201, description="Leave request created")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = activeCompany()->id;

        $validated = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'leave_type_id' => ['required', Rule::exists('leave_types', 'id')->where('company_id', $companyId)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $employee = Employee::where('company_id', $companyId)->findOrFail($validated['employee_id']);
        $data = collect($validated)->except(['employee_id'])->toArray();

        try {
            $leaveRequest = $this->hrService->requestLeave($employee, $data);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success($leaveRequest->load(['employee', 'leaveType']), __('Leave request created'), 201);
    }

    /**
     * Leave requests can only be updated if pending.
     */
    public function update(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        if ($leaveRequest->status !== 'pending') {
            return $this->error(__('Only pending leave requests can be updated.'), 422);
        }

        $companyId = activeCompany()->id;

        $validated = $request->validate([
            'leave_type_id' => ['sometimes', Rule::exists('leave_types', 'id')->where('company_id', $companyId)],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'reason' => ['sometimes', 'string', 'max:1000'],
        ]);

        $leaveRequest->update($validated);

        return $this->success($leaveRequest->fresh()->load(['employee', 'leaveType']), __('Leave request updated'));
    }

    /**
     * Leave requests cannot be deleted — use reject instead.
     */
    public function destroy(LeaveRequest $leaveRequest): JsonResponse
    {
        if ($leaveRequest->status !== 'pending') {
            return $this->error(__('Only pending leave requests can be cancelled.'), 422);
        }

        $leaveRequest->update(['status' => 'cancelled']);

        return $this->success(null, __('Leave request cancelled'));
    }

    /**
     * @OA\Post(
     *     path="/leave-requests/{leaveRequest}/approve",
     *     summary="Approve a leave request",
     *     tags={"Leave Requests"},
     *     @OA\Parameter(name="leaveRequest", in="path", required=true, description="Leave Request ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Leave request approved")
     * )
     */
    public function approve(LeaveRequest $leaveRequest): JsonResponse
    {
        $approver = Employee::where('company_id', activeCompany()->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($approver === null) {
            return $this->error(__('Approver employee record not found.'), 403);
        }

        try {
            $this->hrService->approveLeave($leaveRequest, $approver);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success($leaveRequest->fresh()->load(['employee', 'leaveType', 'approver']), __('Leave request approved'));
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
     *     @OA\Response(response=200, description="Leave request rejected")
     * )
     */
    public function reject(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $approver = Employee::where('company_id', activeCompany()->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($approver === null) {
            return $this->error(__('Approver employee record not found.'), 403);
        }

        $this->hrService->rejectLeave($leaveRequest, $approver, $validated['reason']);

        return $this->success($leaveRequest->fresh(), __('Leave request rejected'));
    }
}
