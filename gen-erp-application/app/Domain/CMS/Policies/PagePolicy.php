<?php

namespace App\Domain\CMS\Policies;

use App\Domain\CMS\Models\Page;
use App\Domain\Auth\Models\User;

class PagePolicy
{
    /**
     * Determine whether the user can view any pages.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the page.
     */
    public function view(User $user, Page $page): bool
    {
        return $user->currentCompany && $user->currentCompany->id === $page->site->company_id;
    }

    /**
     * Determine whether the user can create pages.
     */
    public function create(User $user): bool
    {
        return $user->currentCompany !== null;
    }

    /**
     * Determine whether the user can update the page.
     */
    public function update(User $user, Page $page): bool
    {
        return $user->currentCompany && $user->currentCompany->id === $page->site->company_id;
    }

    /**
     * Determine whether the user can delete the page.
     */
    public function delete(User $user, Page $page): bool
    {
        return $user->currentCompany && $user->currentCompany->id === $page->site->company_id;
    }
}
