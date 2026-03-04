<?php

namespace App\Domain\HR\Actions;

use App\Domain\Auth\Models\User;
use App\Domain\HR\DTOs\AssignTaskData;
use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\EmployeeTask;
use App\Domain\HR\Services\TaskAssignmentService;
use App\Domain\Project\Models\Task;

/**
 * Action to assign a task to an employee
 */
class AssignTaskToEmployeeAction
{
    public function __construct(
        private TaskAssignmentService $taskAssignmentService
    ) {}

    public function execute(AssignTaskData $data): EmployeeTask
    {
        $employee = Employee::findOrFail($data->employeeId);
        $task = Task::findOrFail($data->taskId);
        $assignedBy = User::findOrFail($data->assignedBy);

        return $this->taskAssignmentService->assignTask(
            $employee,
            $task,
            $assignedBy,
            $data->estimatedHours,
            $data->notes
        );
    }
}