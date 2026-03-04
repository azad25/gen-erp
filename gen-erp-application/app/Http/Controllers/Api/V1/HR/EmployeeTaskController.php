<?php

namespace App\Http\Controllers\Api\V1\HR;

use App\Domain\HR\Actions\AssignTaskToEmployeeAction;
use App\Domain\HR\DTOs\AssignTaskData;
use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\EmployeeTask;
use App\Domain\HR\Services\TaskAssignmentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Employee Task Management
 */
class EmployeeTaskController extends Controller
{
    public function __construct(
        private TaskAssignmentService $taskAssignmentService,
        private AssignTaskToEmployeeAction $assignTaskAction
    ) {}

    /**
     * Get tasks for an employee
     * 
     * @param int $employeeId
     * @return JsonResponse
     */
    public function index(Request $request, int $employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        
        $this->authorize('view', $employee);

        $tasks = $this->taskAssignmentService->getEmployeeTasks(
            $employee,
            $request->input('status'),
            $request->input('project_id')
        );

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }

    /**
     * Assign a task to an employee
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
            'task_id' => 'required|exists:tasks,id',
            'estimated_hours' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $data = AssignTaskData::fromArray([
            'employee_id' => $employeeId,
            'task_id' => $validated['task_id'],
            'assigned_by' => auth()->id(),
            'estimated_hours' => $validated['estimated_hours'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $employeeTask = $this->assignTaskAction->execute($data);

        return response()->json([
            'success' => true,
            'message' => 'Task assigned successfully',
            'data' => $employeeTask->load(['task', 'project', 'assignedBy']),
        ], 201);
    }

    /**
     * Get a specific employee task
     * 
     * @param int $employeeId
     * @param int $taskId
     * @return JsonResponse
     */
    public function show(int $employeeId, int $taskId): JsonResponse
    {
        $employeeTask = EmployeeTask::where('employee_id', $employeeId)
            ->where('id', $taskId)
            ->with(['task', 'project', 'assignedBy', 'employee'])
            ->firstOrFail();

        $this->authorize('view', $employeeTask->employee);

        return response()->json([
            'success' => true,
            'data' => $employeeTask,
        ]);
    }

    /**
     * Update employee task
     * 
     * @param Request $request
     * @param int $employeeId
     * @param int $taskId
     * @return JsonResponse
     */
    public function update(Request $request, int $employeeId, int $taskId): JsonResponse
    {
        $employeeTask = EmployeeTask::where('employee_id', $employeeId)
            ->where('id', $taskId)
            ->firstOrFail();

        $this->authorize('update', $employeeTask->employee);

        $validated = $request->validate([
            'status' => 'sometimes|in:assigned,in_progress,completed,on_hold',
            'estimated_hours' => 'sometimes|numeric|min:0',
            'actual_hours' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $employeeTask = $this->taskAssignmentService->updateTaskAssignment(
            $employeeTask,
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data' => $employeeTask->load(['task', 'project']),
        ]);
    }

    /**
     * Unassign a task from an employee
     * 
     * @param int $employeeId
     * @param int $taskId
     * @return JsonResponse
     */
    public function destroy(int $employeeId, int $taskId): JsonResponse
    {
        $employeeTask = EmployeeTask::where('employee_id', $employeeId)
            ->where('id', $taskId)
            ->firstOrFail();

        $this->authorize('update', $employeeTask->employee);

        $this->taskAssignmentService->unassignTask($employeeTask);

        return response()->json([
            'success' => true,
            'message' => 'Task unassigned successfully',
        ]);
    }

    /**
     * Start working on a task
     * 
     * @param int $employeeId
     * @param int $taskId
     * @return JsonResponse
     */
    public function start(int $employeeId, int $taskId): JsonResponse
    {
        $employeeTask = EmployeeTask::where('employee_id', $employeeId)
            ->where('id', $taskId)
            ->firstOrFail();

        $this->authorize('update', $employeeTask->employee);

        $employeeTask = $this->taskAssignmentService->startTask($employeeTask);

        return response()->json([
            'success' => true,
            'message' => 'Task started successfully',
            'data' => $employeeTask,
        ]);
    }

    /**
     * Complete a task
     * 
     * @param Request $request
     * @param int $employeeId
     * @param int $taskId
     * @return JsonResponse
     */
    public function complete(Request $request, int $employeeId, int $taskId): JsonResponse
    {
        $employeeTask = EmployeeTask::where('employee_id', $employeeId)
            ->where('id', $taskId)
            ->firstOrFail();

        $this->authorize('update', $employeeTask->employee);

        $validated = $request->validate([
            'actual_hours' => 'nullable|numeric|min:0',
        ]);

        $employeeTask = $this->taskAssignmentService->completeTask(
            $employeeTask,
            $validated['actual_hours'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Task completed successfully',
            'data' => $employeeTask,
        ]);
    }

    /**
     * Get task statistics for an employee
     * 
     * @param int $employeeId
     * @return JsonResponse
     */
    public function statistics(int $employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        
        $this->authorize('view', $employee);

        $statistics = $this->taskAssignmentService->getEmployeeTaskStatistics($employee);

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }
}