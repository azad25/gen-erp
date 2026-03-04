<?php

namespace App\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\HR\Models\Employee;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view employees');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('view employees') && 
               $employee->company_id === session('active_company_id');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create employees');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('edit employees') && 
               $employee->company_id === session('active_company_id');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('delete employees') && 
               $employee->company_id === session('active_company_id');
    }
}