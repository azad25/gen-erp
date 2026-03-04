<?php

namespace App\Domain\CMS\Policies;

use App\Domain\CMS\Models\Section;
use App\Domain\Auth\Models\User;

class SectionPolicy
{
    /**
     * Determine whether the user can view any sections.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the section.
     */
    public function view(User $user, Section $section): bool
    {
        return $user->currentCompany && $user->currentCompany->id === $section->page->site->company_id;
    }

    /**
     * Determine whether the user can create sections.
     */
    public function create(User $user): bool
    {
        return $user->currentCompany !== null;
    }

    /**
     * Determine whether the user can update the section.
     */
    public function update(User $user, Section $section): bool
    {
        return $user->currentCompany && $user->currentCompany->id === $section->page->site->company_id;
    }

    /**
     * Determine whether the user can delete the section.
     */
    public function delete(User $user, Section $section): bool
    {
        return $user->currentCompany && $user->currentCompany->id === $section->page->site->company_id;
    }
}
