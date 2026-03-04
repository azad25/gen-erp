<?php

namespace App\Domain\HR\Actions;

use App\Domain\HR\DTOs\CreateWorklogData;
use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\EmployeeWorklog;

/**
 * Action to create or update employee worklog
 */
class CreateWorklogAction
{
    public function execute(CreateWorklogData $data): EmployeeWorklog
    {
        $employee = Employee::findOrFail($data->employeeId);

        $worklog = EmployeeWorklog::updateOrCreate([
            'employee_id' => $data->employeeId,
            'log_date' => $data->logDate,
        ], [
            'summary' => $data->summary,
            'mood' => $data->mood,
        ]);

        // Update worklog from time entries
        $worklog->updateFromTimeEntries();

        return $worklog->fresh();
    }
}