<?php

namespace App\Domain\HR\Actions;

use App\Domain\HR\Events\DepartmentDeleted;
use App\Domain\HR\Models\Department;

/**
 * Action for deleting a department.
 */
class DeleteDepartmentAction
{
    public function execute(Department $department): void
    {
        $departmentData = $department->toArray();
        
        $department->delete();

        event(new DepartmentDeleted($departmentData));
    }
}