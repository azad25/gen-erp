<?php

namespace App\Domain\HR\Actions;

use App\Domain\HR\DTOs\CreateDepartmentData;
use App\Domain\HR\Events\DepartmentCreated;
use App\Domain\HR\Models\Department;

/**
 * Action for creating a new department.
 */
class CreateDepartmentAction
{
    public function execute(CreateDepartmentData $data): Department
    {
        $department = Department::create($data->toArray());

        event(new DepartmentCreated($department));

        return $department;
    }
}