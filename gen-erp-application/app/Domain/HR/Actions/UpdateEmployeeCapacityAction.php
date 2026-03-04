<?php

namespace App\Domain\HR\Actions;

use App\Domain\HR\DTOs\UpdateCapacityData;
use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\EmployeeCapacity;
use App\Domain\HR\Services\CapacityPlanningService;

/**
 * Action to update employee capacity
 */
class UpdateEmployeeCapacityAction
{
    public function __construct(
        private CapacityPlanningService $capacityPlanningService
    ) {}

    public function execute(UpdateCapacityData $data): EmployeeCapacity
    {
        $employee = Employee::findOrFail($data->employeeId);

        $capacity = $this->capacityPlanningService->getOrCreateCapacity(
            $employee,
            $data->weekStartDate
        );

        $capacity->update([
            'total_capacity_hours' => $data->totalCapacityHours,
            'allocated_hours' => $data->allocatedHours ?? $capacity->allocated_hours,
        ]);

        $capacity->calculateUtilization();

        return $capacity->fresh();
    }
}