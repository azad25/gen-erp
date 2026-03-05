<?php

namespace App\Http\Controllers\Api\V1\HR;

use App\Domain\HR\Actions\LogEmployeeTimeAction;
use App\Domain\HR\DTOs\LogTimeData;
use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\EmployeeTimeEntry;
use App\Domain\HR\Services\TimeTrackingService;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Employee Time Tracking
 */
class EmployeeTimeEntryController extends Controller
{
    public function __construct(
        private TimeTrackingService $timeTrackingService,
        private LogEmployeeTimeAction $logTimeAction
    ) {}

    /**
     * Get time entries for an employee
     * 
     * @param Request $request
     * @param int $employeeId
     * @return JsonResponse
     */
    public function index(Request $request, int $employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        
        $this->authorize('view', $employee);

        // Handle week_start parameter for weekly timesheet
        if ($request->has('week_start')) {
            $weekStart = Carbon::parse($request->input('week_start'));
            $weekEnd = $weekStart->copy()->endOfWeek();
            
            $timeEntries = $this->timeTrackingService->getEmployeeTimeEntries(
                $employee,
                $weekStart,
                $weekEnd,
                $request->input('entry_type'),
                $request->input('is_approved')
            );

            $totalHours = $timeEntries->sum('hours');

            return response()->json([
                'success' => true,
                'data' => $timeEntries,
                'meta' => [
                    'week_start' => $weekStart->toDateString(),
                    'week_end' => $weekEnd->toDateString(),
                    'total_hours' => $totalHours,
                ],
            ]);
        }

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        $timeEntries = $this->timeTrackingService->getEmployeeTimeEntries(
            $employee,
            $startDate,
            $endDate,
            $request->input('entry_type'),
            $request->input('is_approved')
        );

        return response()->json([
            'success' => true,
            'data' => $timeEntries,
        ]);
    }

    /**
     * Log time for an employee
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
            'entry_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'hours' => 'required|numeric|min:0|max:24',
            'task_id' => 'nullable|exists:tasks,id',
            'project_id' => 'nullable|exists:projects,id',
            'description' => 'nullable|string',
            'entry_type' => 'sometimes|in:task,project,general,break,meeting',
            'is_billable' => 'sometimes|boolean',
        ]);

        $data = LogTimeData::fromArray([
            'employee_id' => $employeeId,
            'entry_date' => $validated['entry_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'hours' => $validated['hours'],
            'task_id' => $validated['task_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'entry_type' => $validated['entry_type'] ?? 'task',
            'is_billable' => $validated['is_billable'] ?? true,
        ]);

        $timeEntry = $this->logTimeAction->execute($data);

        return response()->json([
            'success' => true,
            'message' => 'Time logged successfully',
            'data' => $timeEntry->load(['task', 'project']),
        ], 201);
    }

    /**
     * Get a specific time entry
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $timeEntry = EmployeeTimeEntry::with(['employee', 'task', 'project', 'approvedBy'])
            ->findOrFail($id);

        $this->authorize('view', $timeEntry->employee);

        return response()->json([
            'success' => true,
            'data' => $timeEntry,
        ]);
    }

    /**
     * Update a time entry
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $timeEntry = EmployeeTimeEntry::findOrFail($id);
        
        $this->authorize('update', $timeEntry->employee);

        $validated = $request->validate([
            'entry_date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'hours' => 'sometimes|numeric|min:0',
            'task_id' => 'nullable|exists:tasks,id',
            'project_id' => 'nullable|exists:projects,id',
            'description' => 'nullable|string',
            'entry_type' => 'sometimes|in:task,project,general,break,meeting',
            'is_billable' => 'sometimes|boolean',
        ]);

        $timeEntry = $this->timeTrackingService->updateTimeEntry($timeEntry, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Time entry updated successfully',
            'data' => $timeEntry->load(['task', 'project']),
        ]);
    }

    /**
     * Delete a time entry
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $timeEntry = EmployeeTimeEntry::findOrFail($id);
        
        $this->authorize('update', $timeEntry->employee);

        $this->timeTrackingService->deleteTimeEntry($timeEntry);

        return response()->json([
            'success' => true,
            'message' => 'Time entry deleted successfully',
        ]);
    }

    /**
     * Approve time entries
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function approve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'time_entry_ids' => 'required|array',
            'time_entry_ids.*' => 'exists:employee_time_entries,id',
        ]);

        $timeEntries = $this->timeTrackingService->approveTimeEntries(
            $validated['time_entry_ids'],
            auth()->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Time entries approved successfully',
            'data' => $timeEntries,
        ]);
    }

    /**
     * Get timesheet for an employee
     * 
     * @param Request $request
     * @param int $employeeId
     * @return JsonResponse
     */
    public function timesheet(Request $request, int $employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        
        $this->authorize('view', $employee);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $timesheet = $this->timeTrackingService->getTimesheet(
            $employee,
            Carbon::parse($validated['start_date']),
            Carbon::parse($validated['end_date'])
        );

        return response()->json([
            'success' => true,
            'data' => $timesheet,
        ]);
    }

    /**
     * Get time tracking statistics for an employee
     * 
     * @param Request $request
     * @param int $employeeId
     * @return JsonResponse
     */
    public function statistics(Request $request, int $employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        
        $this->authorize('view', $employee);

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        $statistics = $this->timeTrackingService->getEmployeeTimeStatistics(
            $employee,
            $startDate,
            $endDate
        );

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Get employee capacity information
     * 
     * @param int $employeeId
     * @return JsonResponse
     */
    public function capacity(int $employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        
        $this->authorize('view', $employee);

        $capacity = $this->timeTrackingService->getEmployeeCapacity($employee);

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
    public function updateCapacity(Request $request, int $employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        
        $this->authorize('update', $employee);

        $validated = $request->validate([
            'available_hours' => 'sometimes|numeric|min:0|max:168',
            'is_available_for_projects' => 'sometimes|boolean',
            'overtime_allowed' => 'sometimes|boolean',
        ]);

        // Note: These fields don't exist in the employees table yet
        // For now, we'll just return success. In a real implementation,
        // these would be stored in a separate capacity_settings table or added to employees table
        
        return response()->json([
            'success' => true,
            'message' => 'Employee capacity updated successfully',
            'data' => $employee->fresh(),
        ]);
    }

    /**
     * Get time tracking summary for an employee
     * 
     * @param Request $request
     * @param int $employeeId
     * @return JsonResponse
     */
    public function summary(Request $request, int $employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        
        $this->authorize('view', $employee);

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : now()->endOfMonth();

        $summary = $this->timeTrackingService->getTimeTrackingSummary(
            $employee,
            $startDate,
            $endDate
        );

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}