<?php

namespace App\Domain\HR\Listeners;

use App\Domain\HR\Events\EmployeeTimeLogged;
use App\Domain\HR\Events\TaskAssignedToEmployee;
use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\EmployeeTask;
use App\Domain\HR\Models\EmployeeTimeEntry;
use App\Domain\HR\Services\CapacityPlanningService;
use App\Domain\HR\Services\TimeTrackingService;
use App\Domain\Project\Events\TaskAssigned;
use App\Domain\Project\Events\TaskCompleted;
use App\Domain\Project\Events\TaskStatusChanged;
use App\Domain\Project\Events\TimeLogged;
use Illuminate\Events\Dispatcher;

/**
 * Handles integration between Project Management and HR domains
 */
class ProjectTaskEventListener
{
    public function __construct(
        private CapacityPlanningService $capacityService,
        private TimeTrackingService $timeTrackingService
    ) {}

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            TaskAssigned::class,
            [ProjectTaskEventListener::class, 'handleTaskAssigned']
        );

        $events->listen(
            TaskCompleted::class,
            [ProjectTaskEventListener::class, 'handleTaskCompleted']
        );

        $events->listen(
            TaskStatusChanged::class,
            [ProjectTaskEventListener::class, 'handleTaskStatusChanged']
        );

        $events->listen(
            TimeLogged::class,
            [ProjectTaskEventListener::class, 'handleTimeLogged']
        );
    }

    /**
     * Handle task assignment from Project domain
     */
    public function handleTaskAssigned(TaskAssigned $event): void
    {
        $task = $event->task;
        
        // Only process if task is assigned to a user who is an employee
        if (!$task->assignee_id) {
            return;
        }

        $employee = Employee::whereHas('user', function ($query) use ($task) {
            $query->where('id', $task->assignee_id);
        })->first();

        if (!$employee || !$employee->is_available_for_projects) {
            return;
        }

        // Create or update employee task record
        $employeeTask = EmployeeTask::updateOrCreate([
            'employee_id' => $employee->id,
            'task_id' => $task->id,
        ], [
            'project_id' => $task->project_id,
            'assigned_by' => $event->assignedBy?->id ?? $task->created_by,
            'assigned_at' => now(),
            'estimated_hours' => $task->estimated_hours,
            'status' => 'assigned',
        ]);

        // Update employee capacity
        if ($task->estimated_hours) {
            $this->capacityService->allocateHours($employee, $task->estimated_hours);
        }

        // Fire HR domain event
        event(new TaskAssignedToEmployee($employeeTask));
    }

    /**
     * Handle task completion from Project domain
     */
    public function handleTaskCompleted(TaskCompleted $event): void
    {
        $task = $event->task;

        $employeeTask = EmployeeTask::where('task_id', $task->id)->first();
        
        if (!$employeeTask) {
            return;
        }

        // Update employee task status
        $employeeTask->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Deallocate remaining estimated hours
        if ($employeeTask->estimated_hours) {
            $this->capacityService->deallocateHours(
                $employeeTask->employee,
                $employeeTask->estimated_hours
            );
        }

        // Update daily worklog
        $this->updateEmployeeWorklog($employeeTask->employee, now());
    }

    /**
     * Handle task status changes from Project domain
     */
    public function handleTaskStatusChanged(TaskStatusChanged $event): void
    {
        $task = $event->task;
        $newStatus = $event->newStatus;

        $employeeTask = EmployeeTask::where('task_id', $task->id)->first();
        
        if (!$employeeTask) {
            return;
        }

        // Map project task status to employee task status
        $hrStatus = $this->mapProjectStatusToHRStatus($newStatus);
        
        if ($hrStatus) {
            $updateData = ['status' => $hrStatus];
            
            if ($hrStatus === 'in_progress' && !$employeeTask->started_at) {
                $updateData['started_at'] = now();
            }
            
            $employeeTask->update($updateData);
        }
    }

    /**
     * Handle time logging from Project domain
     */
    public function handleTimeLogged(TimeLogged $event): void
    {
        $timeEntry = $event->timeEntry;

        // Only process if time entry is for a user who is an employee
        $employee = Employee::whereHas('user', function ($query) use ($timeEntry) {
            $query->where('id', $timeEntry->user_id);
        })->first();

        if (!$employee) {
            return;
        }

        // Create employee time entry
        $employeeTimeEntry = EmployeeTimeEntry::create([
            'employee_id' => $employee->id,
            'task_id' => $timeEntry->task_id,
            'project_id' => $timeEntry->project_id,
            'entry_date' => $timeEntry->entry_date,
            'start_time' => $timeEntry->start_time ?? '09:00',
            'end_time' => $timeEntry->end_time ?? '17:00',
            'hours' => $timeEntry->hours,
            'description' => $timeEntry->description,
            'entry_type' => 'work',
            'is_billable' => $timeEntry->is_billable ?? true,
        ]);

        // Update employee task actual hours
        if ($timeEntry->task_id) {
            $employeeTask = EmployeeTask::where('task_id', $timeEntry->task_id)
                ->where('employee_id', $employee->id)
                ->first();
                
            if ($employeeTask) {
                $employeeTask->increment('actual_hours', $timeEntry->hours);
            }
        }

        // Update daily worklog
        $this->updateEmployeeWorklog($employee, $timeEntry->entry_date);

        // Fire HR domain event
        event(new EmployeeTimeLogged($employeeTimeEntry));
    }

    /**
     * Map project task status to HR task status
     */
    private function mapProjectStatusToHRStatus(string $projectStatus): ?string
    {
        return match ($projectStatus) {
            'todo', 'open' => 'assigned',
            'in_progress', 'doing' => 'in_progress',
            'completed', 'done' => 'completed',
            'on_hold', 'blocked' => 'on_hold',
            default => null,
        };
    }

    /**
     * Update employee worklog
     */
    private function updateEmployeeWorklog(Employee $employee, $date): void
    {
        $worklog = $employee->worklogs()->firstOrCreate([
            'log_date' => $date,
        ]);

        $worklog->updateFromTimeEntries();
    }
}