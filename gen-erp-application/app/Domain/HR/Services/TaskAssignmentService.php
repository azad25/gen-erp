<?php

namespace App\Domain\HR\Services;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\EmployeeTask;
use App\Domain\Project\Models\Project;
use App\Domain\Project\Models\Task;
use Illuminate\Database\Eloquent\Collection;

/**
 * Manages task assignments for employees
 */
class TaskAssignmentService
{
    public function __construct(
        private CapacityPlanningService $capacityService
    ) {}

    /**
     * Assign a task to an employee
     */
    public function assignTask(
        Employee $employee,
        Task $task,
        User $assignedBy,
        ?float $estimatedHours = null,
        ?string $notes = null
    ): EmployeeTask {
        // Check if task is already assigned to this employee
        $existingAssignment = EmployeeTask::where('employee_id', $employee->id)
            ->where('task_id', $task->id)
            ->first();

        if ($existingAssignment) {
            throw new \InvalidArgumentException('Task is already assigned to this employee.');
        }

        // Check employee availability
        if (!$employee->is_available_for_projects) {
            throw new \InvalidArgumentException('Employee is not available for project assignments.');
        }

        $employeeTask = EmployeeTask::create([
            'employee_id' => $employee->id,
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'assigned_by' => $assignedBy->id,
            'assigned_at' => now(),
            'estimated_hours' => $estimatedHours ?? $task->estimated_hours ?? 0,
            'status' => 'assigned',
            'notes' => $notes,
        ]);

        // Update capacity if estimated hours provided
        if ($estimatedHours) {
            $this->capacityService->allocateHours($employee, $estimatedHours);
        }

        return $employeeTask;
    }

    /**
     * Unassign a task from an employee
     */
    public function unassignTask(EmployeeTask $employeeTask): void
    {
        // Deallocate hours from capacity
        if ($employeeTask->estimated_hours) {
            $this->capacityService->deallocateHours(
                $employeeTask->employee,
                $employeeTask->estimated_hours
            );
        }

        $employeeTask->delete();
    }

    /**
     * Update task assignment
     */
    public function updateTaskAssignment(
        EmployeeTask $employeeTask,
        array $data
    ): EmployeeTask {
        $oldEstimatedHours = $employeeTask->estimated_hours;
        
        $employeeTask->update($data);

        // Update capacity if estimated hours changed
        if (isset($data['estimated_hours']) && $data['estimated_hours'] !== $oldEstimatedHours) {
            $hoursDiff = $data['estimated_hours'] - $oldEstimatedHours;
            
            if ($hoursDiff > 0) {
                $this->capacityService->allocateHours($employeeTask->employee, $hoursDiff);
            } else {
                $this->capacityService->deallocateHours($employeeTask->employee, abs($hoursDiff));
            }
        }

        return $employeeTask->fresh();
    }

    /**
     * Update task status
     */
    public function updateTaskStatus(EmployeeTask $employeeTask, string $status): bool
    {
        $validStatuses = ['assigned', 'in_progress', 'completed', 'cancelled', 'on_hold'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        $updateData = ['status' => $status];

        // Set timestamps based on status
        if ($status === 'in_progress' && $employeeTask->status === 'assigned') {
            $updateData['started_at'] = now();
        } elseif ($status === 'completed') {
            $updateData['completed_at'] = now();
        }

        return $employeeTask->update($updateData);
    }

    /**
     * Start working on a task
     */
    public function startTask(EmployeeTask $employeeTask): EmployeeTask
    {
        if ($employeeTask->status !== 'assigned') {
            throw new \InvalidArgumentException('Task must be in assigned status to start.');
        }

        $employeeTask->markAsStarted();
        return $employeeTask->fresh();
    }

    /**
     * Complete a task
     */
    public function completeTask(EmployeeTask $employeeTask, ?float $actualHours = null): EmployeeTask
    {
        if (!in_array($employeeTask->status, ['assigned', 'in_progress'])) {
            throw new \InvalidArgumentException('Task must be assigned or in progress to complete.');
        }

        $updateData = [
            'status' => 'completed',
            'completed_at' => now(),
        ];

        if ($actualHours !== null) {
            $updateData['actual_hours'] = $actualHours;
        }

        $employeeTask->update($updateData);

        // Deallocate remaining estimated hours
        if ($employeeTask->estimated_hours) {
            $this->capacityService->deallocateHours(
                $employeeTask->employee,
                $employeeTask->estimated_hours
            );
        }

        return $employeeTask->fresh();
    }

    /**
     * Get tasks assigned to an employee
     */
    public function getEmployeeTasks(
        Employee $employee,
        ?string $status = null,
        ?int $projectId = null
    ): Collection {
        return EmployeeTask::where('employee_id', $employee->id)
            ->when($status, fn($q, $s) => $q->where('status', $s))
            ->when($projectId, fn($q, $p) => $q->where('project_id', $p))
            ->with(['task', 'project', 'assignedBy'])
            ->orderBy('assigned_at', 'desc')
            ->get();
    }

    /**
     * Get tasks for a project
     */
    public function getProjectTasks(Project $project, ?string $status = null): Collection
    {
        return EmployeeTask::where('project_id', $project->id)
            ->when($status, fn($q, $s) => $q->where('status', $s))
            ->with(['employee', 'task', 'assignedBy'])
            ->orderBy('assigned_at', 'desc')
            ->get();
    }

    /**
     * Get overdue tasks for a company
     */
    public function getOverdueTasks(Company $company): Collection
    {
        return EmployeeTask::whereHas('employee', fn($q) => $q->where('company_id', $company->id))
            ->whereHas('task', fn($q) => $q->where('due_date', '<', now()))
            ->where('status', '!=', 'completed')
            ->with(['employee', 'task', 'project'])
            ->get();
    }

    /**
     * Get task statistics for an employee
     */
    public function getEmployeeTaskStatistics(Employee $employee): array
    {
        $tasks = EmployeeTask::where('employee_id', $employee->id)->get();
        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'completed')->count();

        return [
            'total_tasks' => $totalTasks,
            'assigned_tasks' => $tasks->where('status', 'assigned')->count(),
            'in_progress_tasks' => $tasks->where('status', 'in_progress')->count(),
            'completed_tasks' => $completedTasks,
            'on_hold_tasks' => $tasks->where('status', 'on_hold')->count(),
            'overdue_tasks' => $tasks->filter(fn($task) => $task->isOverdue())->count(),
            'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
            'total_estimated_hours' => $tasks->sum('estimated_hours'),
            'total_actual_hours' => $tasks->sum('actual_hours'),
            'average_completion_time' => $this->calculateAverageCompletionTime($tasks),
        ];
    }

    /**
     * Bulk assign tasks to multiple employees
     */
    public function bulkAssignTasks(
        array $taskIds,
        array $employeeIds,
        User $assignedBy,
        ?float $estimatedHours = null
    ): array {
        $assignments = [];
        
        foreach ($taskIds as $taskId) {
            $task = Task::findOrFail($taskId);
            
            foreach ($employeeIds as $employeeId) {
                $employee = Employee::findOrFail($employeeId);
                
                try {
                    $assignments[] = $this->assignTask(
                        $employee,
                        $task,
                        $assignedBy,
                        $estimatedHours
                    );
                } catch (\InvalidArgumentException $e) {
                    // Skip if already assigned or employee not available
                    continue;
                }
            }
        }
        
        return $assignments;
    }

    /**
     * Calculate average completion time for tasks
     */
    private function calculateAverageCompletionTime(Collection $tasks): ?float
    {
        $completedTasks = $tasks->where('status', 'completed')
            ->whereNotNull('assigned_at')
            ->whereNotNull('completed_at');

        if ($completedTasks->isEmpty()) {
            return null;
        }

        $totalHours = $completedTasks->sum(function ($task) {
            return $task->assigned_at->diffInHours($task->completed_at);
        });

        return round($totalHours / $completedTasks->count(), 2);
    }
}