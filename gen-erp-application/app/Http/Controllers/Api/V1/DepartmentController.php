<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Department;
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
    /**
     * @OA\Get(
     *     path="/departments",
     *     summary="List all departments",
     *     tags={"Departments"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Department")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $departments = Department::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($departments);
    }

    /**
     * @OA\Get(
     *     path="/departments/{id}",
     *     summary="Get a specific department",
     *     tags={"Departments"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Department ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Department")
     *         )
     *     )
     * )
     */
    public function show(Department $department): JsonResponse
    {
        $department->load(['employees']);

        return $this->success($department);
    }

    /**
     * @OA\Post(
     *     path="/departments",
     *     summary="Create a new department",
     *     tags={"Departments"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Department created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Department"),
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
        ]);

        $validated['company_id'] = activeCompany()->id;

        $department = Department::create($validated);

        return $this->success($department, __('Department created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/departments/{id}",
     *     summary="Update a department",
     *     tags={"Departments"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Department ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Department updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Department"),
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
        ]);

        $department->update($validated);

        return $this->success($department->fresh(), __('Department updated'));
    }

    /**
     * @OA\Delete(
     *     path="/departments/{id}",
     *     summary="Delete a department",
     *     tags={"Departments"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Department ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Department deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Department $department): JsonResponse
    {
        $department->delete();

        return $this->success(null, __('Department deleted'));
    }
}
