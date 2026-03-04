<?php

namespace App\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\Customer\Models\Customer;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view customers');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('view customers') && 
               $customer->company_id === session('active_company_id');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create customers');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('edit customers') && 
               $customer->company_id === session('active_company_id');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('delete customers') && 
               $customer->company_id === session('active_company_id');
    }
}