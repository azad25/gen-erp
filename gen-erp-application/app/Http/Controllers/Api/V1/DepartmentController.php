<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\HR\Contracts\HRServiceInterface;
use App\Domain\HR\DTOs\CreateDepartmentData;
use App\Domain\HR\DTOs\UpdateDepartmentData;
use App\Domain\HR\Models\Department;
use App\Http\Resources\DepartmentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Departments",
 *     description="Department management"
 * )
 * REST API v1 controller for Department CRUD operations.
 */
class DepartmentController extends BaseApiController
{
    public function __construct(
        private HRServiceInterface $hrService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/v1/departments",
     *     summary="List all departments",
     *     tags={"Departments"},
     *
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
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
        $departments = $this->hrService
            ->getDepartments(activeCompany(), $request->get('search'))
            ->paginate($request->integer('per_page', 15));

        return $this->paginated(DepartmentResource::collection($departments));
    }

    /**
     * @OA\Get(
     *     path="/api/v1/departments/{id}",
     *     summary="Get a specific department",
     *     tags={"Departments"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Department ID", @OA\Schema(type="integer")),
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
    public function show(Department $department): JsonResponse
    {
        $department->load(['employees', 'manager', 'parent', 'children']);

        return $this->success(new DepartmentResource($department));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/departments",
     *     summary="Create a new department",
     *     tags={"Departments"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Department created",
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
            'description' => ['nullable', 'string'],
            'code' => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', 'integer', 'exists:departments,id'],
            'manager_id' => ['nullable', 'integer', 'exists:employees,id'],
        ]);

        $departmentData = new CreateDepartmentData(
            company_id: activeCompany()->id,
            name: $validated['name'],
            description: $validated['description'] ?? null,
            code: $validated['code'] ?? null,
            parent_id: $validated['parent_id'] ?? null,
            manager_id: $validated['manager_id'] ?? null,
        );

        $department = $this->hrService->createDepartment($departmentData);

        return $this->success(new DepartmentResource($department), __('Department created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/departments/{id}",
     *     summary="Update a department",
     *     tags={"Departments"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Department ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Department updated",
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
    public function update(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'code' => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', 'integer', 'exists:departments,id'],
            'manager_id' => ['nullable', 'integer', 'exists:employees,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $updateData = new UpdateDepartmentData(
            name: $validated['name'] ?? null,
            description: $validated['description'] ?? null,
            code: $validated['code'] ?? null,
            parent_id: $validated['parent_id'] ?? null,
            manager_id: $validated['manager_id'] ?? null,
            is_active: $validated['is_active'] ?? null,
        );

        $department = $this->hrService->updateDepartment($department, $updateData);

        return $this->success(new DepartmentResource($department), __('Department updated'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/departments/{id}",
     *     summary="Delete a department",
     *     tags={"Departments"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Department ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Department deleted",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Department $department): JsonResponse
    {
        $this->hrService->deleteDepartment($department);

        return $this->success(null, __('Department deleted'));
    }
}
