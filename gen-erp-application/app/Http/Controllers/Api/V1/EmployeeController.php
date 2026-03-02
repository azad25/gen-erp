<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Employee;
use App\Services\HRService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Employees",
 *     description="Employee management"
 * )
 * REST API v1 controller for Employee operations.
 */
class EmployeeController extends BaseApiController
{
    public function __construct(
        private readonly HRService $hrService
    ) {}

    /**
     * @OA\Get(
     *     path="/employees",
     *     summary="List all employees",
     *     tags={"Employees"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
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
            ->where('company_id', activeCompany()->id)
            ->when($request->get('search'), fn ($q, $s) => $q->where(function ($q) use ($s): void {
                $q->where('first_name', 'LIKE', "%{$s}%")
                    ->orWhere('last_name', 'LIKE', "%{$s}%")
                    ->orWhere('employee_id_number', 'LIKE', "%{$s}%");
            }))
            ->when($request->get('department_id'), fn ($q, $d) => $q->where('department_id', $d))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->with(['department', 'designation'])
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
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="department_id", type="integer"),
     *             @OA\Property(property="designation_id", type="integer"),
     *             @OA\Property(property="joining_date", type="string", format="date"),
     *             @OA\Property(property="basic_salary", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Employee created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Employee"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = activeCompany()->id;

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'designation_id' => ['nullable', Rule::exists('designations', 'id')->where('company_id', $companyId)],
            'joining_date' => ['required', 'date'],
            'basic_salary' => ['nullable', 'integer', 'min:0'],
            'address' => ['nullable', 'string', 'max:500'],
            'nid_number' => ['nullable', 'string', 'max:50'],
            'custom_fields' => ['nullable', 'array'],
        ]);

        $customFields = $validated['custom_fields'] ?? [];
        unset($validated['custom_fields']);

        $employee = $this->hrService->createEmployee(
            activeCompany(),
            $validated,
            $customFields,
        );

        return $this->success($employee->load(['department', 'designation']), __('Employee created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/employees/{id}",
     *     summary="Update an employee",
     *     tags={"Employees"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Employee ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent()),
     *     @OA\Response(
     *         response=200,
     *         description="Employee updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Employee"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Employee $employee): JsonResponse
    {
        $companyId = activeCompany()->id;

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'designation_id' => ['nullable', Rule::exists('designations', 'id')->where('company_id', $companyId)],
            'basic_salary' => ['nullable', 'integer', 'min:0'],
            'address' => ['nullable', 'string', 'max:500'],
            'custom_fields' => ['nullable', 'array'],
        ]);

        $customFields = $validated['custom_fields'] ?? [];
        unset($validated['custom_fields']);

        $employee = $this->hrService->updateEmployee(
            $employee,
            $validated,
            $customFields,
        );

        return $this->success($employee->load(['department', 'designation']), __('Employee updated'));
    }

    /**
     * Employees cannot be deleted — use terminate instead.
     */
    public function destroy(Employee $employee): JsonResponse
    {
        return $this->error(__('Employees cannot be deleted. Use the terminate endpoint instead.'), 403);
    }

    /**
     * @OA\Post(
     *     path="/employees/{employee}/terminate",
     *     summary="Terminate an employee",
     *     tags={"Employees"},
     *     @OA\Parameter(name="employee", in="path", required=true, description="Employee ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="termination_date", type="string", format="date"),
     *             @OA\Property(property="reason", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Employee terminated")
     * )
     */
    public function terminate(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate([
            'termination_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->hrService->terminateEmployee(
            $employee,
            \Carbon\Carbon::parse($validated['termination_date']),
            $validated['reason'],
        );

        return $this->success($employee->fresh(), __('Employee terminated'));
    }
}
