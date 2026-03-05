<?php

namespace App\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\CMS\Models\Contact;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view cms');
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->hasPermissionTo('view cms') && 
               $contact->company_id === session('active_company_id');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create cms');
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->hasPermissionTo('edit cms') && 
               $contact->company_id === session('active_company_id');
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->hasPermissionTo('delete cms') && 
               $contact->company_id === session('active_company_id');
    }

    public function markAsContacted(User $user, Contact $contact): bool
    {
        return $user->hasPermissionTo('edit cms') && 
               $contact->company_id === session('active_company_id');
    }

    public function markAsResolved(User $user, Contact $contact): bool
    {
        return $user->hasPermissionTo('edit cms') && 
               $contact->company_id === session('active_company_id');
    }

    public function markAsSpam(User $user, Contact $contact): bool
    {
        return $user->hasPermissionTo('edit cms') && 
               $contact->company_id === session('active_company_id');
    }
}
