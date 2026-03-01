<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Employees",
 *     description="Employee management (read-only)"
 * )
 * REST API v1 controller for Employee operations.
 */
class EmployeeController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/employees",
     *     summary="List all employees",
     *     tags={"Employees"},
     *     @OA\Parameter(name="department_id", in="query", description="Department ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", description="Employee status", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Employee")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $employees = Employee::query()
            ->when($request->get('department_id'), fn ($q, $d) => $q->where('department_id', $d))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('first_name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($employees);
    }

    /**
     * @OA\Get(
     *     path="/employees/{id}",
     *     summary="Get a specific employee",
     *     tags={"Employees"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Employee ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Employee")
     *         )
     *     )
     * )
     */
    public function show(Employee $employee): JsonResponse
    {
        return $this->success($employee->load(['department', 'designation']));
    }

    /**
     * @OA\Post(
     *     path="/employees",
     *     summary="Create a new employee",
     *     tags={"Employees"},
     *     @OA\Response(
     *         response=501,
     *         description="Not implemented",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        return $this->error('Employee creation via API is not yet supported.', 501);
    }

    /**
     * @OA\Put(
     *     path="/employees/{id}",
     *     summary="Update an employee",
     *     tags={"Employees"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Employee ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=501,
     *         description="Not implemented",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Employee $employee): JsonResponse
    {
        return $this->error('Employee updates via API are not yet supported.', 501);
    }

    /**
     * @OA\Delete(
     *     path="/employees/{id}",
     *     summary="Delete an employee",
     *     tags={"Employees"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Employee ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=403,
     *         description="Not allowed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Employee $employee): JsonResponse
    {
        return $this->error('Employee deletion via API is not supported.', 403);
    }
}
