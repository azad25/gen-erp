<?php

namespace App\Http\Controllers\Api\V1\HR;

use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\EmployeeAvailability;
use App\Http\Controllers\Controller;
use App\Services\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Employee Availability Management
 */
class EmployeeAvailabilityController extends Controller
{
    /**
     * Get availability for an employee
     * 
     * @param int $employeeId
     * @return JsonResponse
     */
    public function index(Request $request, int $employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        
        $this->authorize('view', $employee);

        $availability = EmployeeAvailability::where('employee_id', $employeeId)
            ->with(['employee'])
            ->when($request->input('start_date'), function ($query, $startDate) {
                $query->where('date', '>=', $startDate);
            })
            ->when($request->input('end_date'), function ($query, $endDate) {
                $query->where('date', '<=', $endDate);
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $availability,
        ]);
    }

    /**
     * Get availability calendar for all employees
     * 
     * @return JsonResponse
     */
    public function calendar(Request $request): JsonResponse
    {
        $companyId = CompanyContext::activeId();

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $availability = EmployeeAvailability::whereHas('employee', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->with(['employee'])
        ->whereBetween('date', [$startDate, $endDate])
        ->orderBy('date', 'asc')
        ->get()
        ->groupBy('employee_id');

        return response()->json([
            'success' => true,
            'data' => $availability,
        ]);
    }

    /**
     * Set availability for an employee
     * 
     * @param Request $request
     * @param int $employeeId
     * @return JsonResponse
     */
    public function store(Request $request, int $employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        
        $this->authorize('update', $employee);

        $validated = $request->validate([
            'date' => 'required|date',
            'status' => 'required|in:available,unavailable,partial',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $availability = EmployeeAvailability::updateOrCreate(
            [
                'employee_id' => $employeeId,
                'date' => $validated['date'],
            ],
            [
                'status' => $validated['status'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Availability set successfully',
            'data' => $availability->load('employee'),
        ], 201);
    }

    /**
     * Bulk set availability for an employee
     * 
     * @param Request $request
     * @param int $employeeId
     * @return JsonResponse
     */
    public function bulkStore(Request $request, int $employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        
        $this->authorize('update', $employee);

        $validated = $request->validate([
            'dates' => 'required|array',
            'dates.*' => 'required|date',
            'status' => 'required|in:available,unavailable,partial',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $created = [];
        foreach ($validated['dates'] as $date) {
            $availability = EmployeeAvailability::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'date' => $date,
                ],
                [
                    'status' => $validated['status'],
                    'start_time' => $validated['start_time'] ?? null,
                    'end_time' => $validated['end_time'] ?? null,
                    'reason' => $validated['reason'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]
            );
            $created[] = $availability;
        }

        return response()->json([
            'success' => true,
            'message' => 'Availability set for ' . count($created) . ' dates',
            'data' => $created,
        ], 201);
    }

    /**
     * Get a specific availability record
     * 
     * @param int $employeeId
     * @param int $availabilityId
     * @return JsonResponse
     */
    public function show(int $employeeId, int $availabilityId): JsonResponse
    {
        $availability = EmployeeAvailability::where('employee_id', $employeeId)
            ->where('id', $availabilityId)
            ->with(['employee'])
            ->firstOrFail();

        $this->authorize('view', $availability->employee);

        return response()->json([
            'success' => true,
            'data' => $availability,
        ]);
    }

    /**
     * Update availability
     * 
     * @param Request $request
     * @param int $employeeId
     * @param int $availabilityId
     * @return JsonResponse
     */
    public function update(Request $request, int $employeeId, int $availabilityId): JsonResponse
    {
        $availability = EmployeeAvailability::where('employee_id', $employeeId)
            ->where('id', $availabilityId)
            ->firstOrFail();

        $this->authorize('update', $availability->employee);

        $validated = $request->validate([
            'status' => 'sometimes|in:available,unavailable,partial',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $availability->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Availability updated successfully',
            'data' => $availability->load('employee'),
        ]);
    }

    /**
     * Remove availability record
     * 
     * @param int $employeeId
     * @param int $availabilityId
     * @return JsonResponse
     */
    public function destroy(int $employeeId, int $availabilityId): JsonResponse
    {
        $availability = EmployeeAvailability::where('employee_id', $employeeId)
            ->where('id', $availabilityId)
            ->firstOrFail();

        $this->authorize('update', $availability->employee);

        $availability->delete();

        return response()->json([
            'success' => true,
            'message' => 'Availability record removed successfully',
        ]);
    }

    /**
     * Get availability statistics
     * 
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        $companyId = CompanyContext::activeId();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $totalRecords = EmployeeAvailability::whereHas('employee', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->whereBetween('date', [$startDate, $endDate])
        ->count();

        $availableCount = EmployeeAvailability::whereHas('employee', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->whereBetween('date', [$startDate, $endDate])
        ->where('status', 'available')
        ->count();

        $unavailableCount = EmployeeAvailability::whereHas('employee', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->whereBetween('date', [$startDate, $endDate])
        ->where('status', 'unavailable')
        ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_records' => $totalRecords,
                'available' => $availableCount,
                'unavailable' => $unavailableCount,
                'partial' => $totalRecords - $availableCount - $unavailableCount,
            ],
        ]);
    }
}
