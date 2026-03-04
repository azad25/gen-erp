<?php

namespace App\Http\Controllers\Api\V1\HR;

use App\Domain\Auth\Models\Company;
use App\Domain\HR\Actions\UpdateEmployeeCapacityAction;
use App\Domain\HR\DTOs\UpdateCapacityData;
use App\Domain\HR\Models\Employee;
use App\Domain\HR\Services\CapacityPlanningService;
use App\Http\Controllers\Controller;
use App\Services\CompanyContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Employee Capacity Planning
 */
class EmployeeCapacityController extends Controller
{
    public function __construct(
        private CapacityPlanningService $capacityService,
        private UpdateEmployeeCapacityAction $updateCapacityAction
    ) {}

    /**
     * Get capacity for an employee
     * 
     * @param Request $request
     * @param int $employeeId
     * @return JsonResponse
     */
    public function index(Request $request, int $employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        
        $this->authorize('view', $employee);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $capacity = $this->capacityService->getEmployeeCapacity(
            $employee,
            Carbon::parse($validated['start_date']),
            Carbon::parse($validated['end_date'])
        );

        return response()->json([
            'success' => true,
            'data' => $capacity,
        ]);
    }

    /**
     * Update employee capacity
     * 
     * @param Request $request
     * @param int $employeeId
     * @return JsonResponse
     */
    public function update(Request $request, int $employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        
        $this->authorize('update', $employee);

        $validated = $request->validate([
            'week_start_date' => 'required|date',
            'total_capacity_hours' => 'required|integer|min:0',
            'allocated_hours' => 'nullable|integer|min:0',
        ]);

        $data = UpdateCapacityData::fromArray([
            'employee_id' => $employeeId,
            'week_start_date' => $validated['week_start_date'],
            'total_capacity_hours' => $validated['total_capacity_hours'],
            'allocated_hours' => $validated['allocated_hours'] ?? null,
        ]);

        $capacity = $this->updateCapacityAction->execute($data);

        return response()->json([
            'success' => true,
            'message' => 'Capacity updated successfully',
            'data' => $capacity,
        ]);
    }

    /**
     * Get team capacity overview
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function overview(Request $request): JsonResponse
    {
        $company = CompanyContext::get();

        $weekStartDate = $request->input('week_start_date') 
            ? Carbon::parse($request->input('week_start_date')) 
            : null;

        $overview = $this->capacityService->getTeamCapacityOverview($company, $weekStartDate);

        return response()->json([
            'success' => true,
            'data' => $overview,
        ]);
    }

    /**
     * Find available employees
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function available(Request $request): JsonResponse
    {
        $company = CompanyContext::get();

        $validated = $request->validate([
            'required_hours' => 'required|integer|min:1',
            'week_start_date' => 'nullable|date',
            'required_skills' => 'nullable|array',
            'required_skills.*' => 'string',
        ]);

        $weekStartDate = isset($validated['week_start_date']) 
            ? Carbon::parse($validated['week_start_date']) 
            : null;

        $employees = $this->capacityService->findAvailableEmployees(
            $company,
            $validated['required_hours'],
            $weekStartDate,
            $validated['required_skills'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $employees,
        ]);
    }

    /**
     * Get utilization trends for an employee
     * 
     * @param Request $request
     * @param int $employeeId
     * @return JsonResponse
     */
    public function trends(Request $request, int $employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        
        $this->authorize('view', $employee);

        $numberOfWeeks = $request->input('weeks', 12);

        $trends = $this->capacityService->getEmployeeUtilizationTrends(
            $employee,
            $numberOfWeeks
        );

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }

    /**
     * Get overallocated employees
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function overallocated(Request $request): JsonResponse
    {
        $company = CompanyContext::get();

        $weekStartDate = $request->input('week_start_date') 
            ? Carbon::parse($request->input('week_start_date')) 
            : null;

        $employees = $this->capacityService->getOverallocatedEmployees($company, $weekStartDate);

        return response()->json([
            'success' => true,
            'data' => $employees,
        ]);
    }
}