<?php

namespace App\Domain\HR\Actions;

use App\Domain\HR\DTOs\UpdateDepartmentData;
use App\Domain\HR\Events\DepartmentUpdated;
use App\Domain\HR\Models\Department;

/**
 * Action for updating a department.
 */
class UpdateDepartmentAction
{
    public function execute(Department $department, UpdateDepartmentData $data): Department
    {
        $department->update($data->toArray());

        event(new DepartmentUpdated($department));

        return $department->fresh();
    }
}