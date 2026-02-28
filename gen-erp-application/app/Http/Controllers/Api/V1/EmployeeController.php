<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REST API v1 controller for Employee operations.
 */
class EmployeeController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $employees = Employee::query()
            ->when($request->get('department_id'), fn ($q, $d) => $q->where('department_id', $d))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('first_name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($employees);
    }

    public function show(Employee $employee): JsonResponse
    {
        return $this->success($employee->load(['department', 'designation']));
    }

    public function store(Request $request): JsonResponse
    {
        return $this->error('Employee creation via API is not yet supported.', 501);
    }

    public function update(Request $request, Employee $employee): JsonResponse
    {
        return $this->error('Employee updates via API are not yet supported.', 501);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        return $this->error('Employee deletion via API is not supported.', 403);
    }
}
