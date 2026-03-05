<?php

namespace App\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\CMS\Models\Review;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReviewPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view cms');
    }

    public function view(User $user, Review $review): bool
    {
        return $user->hasPermissionTo('view cms') && 
               $review->company_id === session('active_company_id');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create cms');
    }

    public function update(User $user, Review $review): bool
    {
        return $user->hasPermissionTo('edit cms') && 
               $review->company_id === session('active_company_id');
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->hasPermissionTo('delete cms') && 
               $review->company_id === session('active_company_id');
    }

    public function approve(User $user, Review $review): bool
    {
        return $user->hasPermissionTo('edit cms') && 
               $review->company_id === session('active_company_id');
    }

    public function reject(User $user, Review $review): bool
    {
        return $user->hasPermissionTo('edit cms') && 
               $review->company_id === session('active_company_id');
    }
}
