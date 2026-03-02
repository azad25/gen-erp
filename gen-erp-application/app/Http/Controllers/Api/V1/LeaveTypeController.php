<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Leave Types",
 *     description="Leave type management"
 * )
 * REST API v1 controller for Leave Type CRUD operations.
 */
class LeaveTypeController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/leave-types",
     *     summary="List all leave types",
     *     tags={"Leave Types"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="is_active", in="query", description="Active status", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/LeaveType")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $types = LeaveType::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->when($request->get('is_active'), fn ($q, $s) => $q->where('is_active', $s))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($types);
    }

    /**
     * @OA\Get(
     *     path="/leave-types/{id}",
     *     summary="Get a specific leave type",
     *     tags={"Leave Types"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Leave Type ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/LeaveType")
     *         )
     *     )
     * )
     */
    public function show(LeaveType $leaveType): JsonResponse
    {
        return $this->success($leaveType);
    }

    /**
     * @OA\Post(
     *     path="/leave-types",
     *     summary="Create a new leave type",
     *     tags={"Leave Types"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="days_allowed", type="integer"),
     *             @OA\Property(property="is_paid", type="boolean"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Leave type created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/LeaveType"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'days_allowed' => ['required', 'integer', 'min:0'],
            'is_paid' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['company_id'] = activeCompany()->id;

        $type = LeaveType::create($validated);

        return $this->success($type, __('Leave type created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/leave-types/{id}",
     *     summary="Update a leave type",
     *     tags={"Leave Types"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Leave Type ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="days_allowed", type="integer"),
     *             @OA\Property(property="is_paid", type="boolean"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Leave type updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/LeaveType"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, LeaveType $leaveType): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50'],
            'days_allowed' => ['sometimes', 'integer', 'min:0'],
            'is_paid' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $leaveType->update($validated);

        return $this->success($leaveType->fresh(), __('Leave type updated'));
    }

    /**
     * @OA\Delete(
     *     path="/leave-types/{id}",
     *     summary="Delete a leave type",
     *     tags={"Leave Types"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Leave Type ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Leave type deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(LeaveType $leaveType): JsonResponse
    {
        $leaveType->delete();

        return $this->success(null, __('Leave type deleted'));
    }
}
