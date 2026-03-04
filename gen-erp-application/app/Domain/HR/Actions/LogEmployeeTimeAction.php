<?php

namespace App\Domain\HR\Actions;

use App\Domain\HR\DTOs\LogTimeData;
use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\EmployeeTimeEntry;
use App\Domain\HR\Services\TimeTrackingService;

/**
 * Action to log time for an employee
 */
class LogEmployeeTimeAction
{
    public function __construct(
        private TimeTrackingService $timeTrackingService
    ) {}

    public function execute(LogTimeData $data): EmployeeTimeEntry
    {
        $employee = Employee::findOrFail($data->employeeId);

        return $this->timeTrackingService->logTime(
            $employee,
            $data->entryDate,
            $data->toArray()
        );
    }
}