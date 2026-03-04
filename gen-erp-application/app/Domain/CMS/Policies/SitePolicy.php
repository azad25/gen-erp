<?php

namespace App\Domain\CMS\Policies;

use App\Domain\CMS\Models\Site;
use App\Domain\Auth\Models\User;

class SitePolicy
{
    /**
     * Determine whether the user can view any sites.
     */
    public function viewAny(User $user): bool
    {
        return true; // Any authenticated user can view sites for their company
    }

    /**
     * Determine whether the user can view the site.
     */
    public function view(User $user, Site $site): bool
    {
        // Check if user's current company matches the site's company
        return $user->currentCompany && $user->currentCompany->id === $site->company_id;
    }

    /**
     * Determine whether the user can create sites.
     */
    public function create(User $user): bool
    {
        return $user->currentCompany !== null;
    }

    /**
     * Determine whether the user can update the site.
     */
    public function update(User $user, Site $site): bool
    {
        return $user->currentCompany && $user->currentCompany->id === $site->company_id;
    }

    /**
     * Determine whether the user can delete the site.
     */
    public function delete(User $user, Site $site): bool
    {
        return $user->currentCompany && $user->currentCompany->id === $site->company_id;
    }
}
