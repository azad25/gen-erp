<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\HR\Contracts\HRServiceInterface;
use App\Http\Resources\LeaveTypeResource;
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
    public function __construct(
        private readonly HRServiceInterface $hrService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/v1/leave-types",
     *     summary="List all leave types",
     *     tags={"Leave Types"},
     *
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="is_active", in="query", description="Active status", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $types = $this->hrService->getLeaveTypes(
            activeCompany()->id,
            $request->get('search'),
            $request->boolean('is_active'),
            $request->integer('per_page', 15)
        );

        return $this->paginated($types, LeaveTypeResource::class);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/leave-types/{id}",
     *     summary="Get a specific leave type",
     *     tags={"Leave Types"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Leave Type ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $leaveType = $this->hrService->getLeaveType(activeCompany()->id, $id);

        return $this->success(new LeaveTypeResource($leaveType));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/leave-types",
     *     summary="Create a new leave type",
     *     tags={"Leave Types"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="days_allowed", type="integer"),
     *             @OA\Property(property="is_paid", type="boolean"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Leave type created",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
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

        $type = $this->hrService->createLeaveType(activeCompany()->id, $validated);

        return $this->success(new LeaveTypeResource($type), __('Leave type created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/leave-types/{id}",
     *     summary="Update a leave type",
     *     tags={"Leave Types"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Leave Type ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="days_allowed", type="integer"),
     *             @OA\Property(property="is_paid", type="boolean"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Leave type updated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $leaveType = $this->hrService->getLeaveType(activeCompany()->id, $id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50'],
            'days_allowed' => ['sometimes', 'integer', 'min:0'],
            'is_paid' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $updatedType = $this->hrService->updateLeaveType($leaveType, $validated);

        return $this->success(new LeaveTypeResource($updatedType), __('Leave type updated'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/leave-types/{id}",
     *     summary="Delete a leave type",
     *     tags={"Leave Types"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Leave Type ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Leave type deleted",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $leaveType = $this->hrService->getLeaveType(activeCompany()->id, $id);
        $this->hrService->deleteLeaveType($leaveType);

        return $this->success(null, __('Leave type deleted'));
    }
}
