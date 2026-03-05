<?php

namespace App\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\CMS\Models\Wishlist;
use Illuminate\Auth\Access\HandlesAuthorization;

class WishlistPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view cms');
    }

    public function view(User $user, Wishlist $wishlist): bool
    {
        return $user->hasPermissionTo('view cms') && 
               $wishlist->company_id === session('active_company_id');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create cms');
    }

    public function update(User $user, Wishlist $wishlist): bool
    {
        return $user->hasPermissionTo('edit cms') && 
               $wishlist->company_id === session('active_company_id');
    }

    public function delete(User $user, Wishlist $wishlist): bool
    {
        return $user->hasPermissionTo('delete cms') && 
               $wishlist->company_id === session('active_company_id');
    }
}
