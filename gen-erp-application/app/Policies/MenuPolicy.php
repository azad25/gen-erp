<?php

namespace App\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\CMS\Models\Menu;
use Illuminate\Auth\Access\HandlesAuthorization;

class MenuPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view cms');
    }

    public function view(User $user, Menu $menu): bool
    {
        return $user->hasPermissionTo('view cms') && 
               $menu->company_id === session('active_company_id');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create cms');
    }

    public function update(User $user, Menu $menu): bool
    {
        return $user->hasPermissionTo('edit cms') && 
               $menu->company_id === session('active_company_id');
    }

    public function delete(User $user, Menu $menu): bool
    {
        return $user->hasPermissionTo('delete cms') && 
               $menu->company_id === session('active_company_id');
    }
}
