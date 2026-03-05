<?php

namespace App\Policies;

use App\Domain\Auth\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SEOPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view cms');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('view cms');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create cms');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('edit cms');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('delete cms');
    }

    public function manageSitemap(User $user): bool
    {
        return $user->hasPermissionTo('edit cms');
    }

    public function manageRobots(User $user): bool
    {
        return $user->hasPermissionTo('edit cms');
    }

    public function manageStructuredData(User $user): bool
    {
        return $user->hasPermissionTo('edit cms');
    }
}
