<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Events\UserRemovedFromCompany;
use App\Domain\Auth\Models\CompanyUser;
use App\Domain\Auth\Models\User;

/**
 * Remove a user from a company.
 */
class RemoveUserFromCompanyAction
{
    public function execute(User $user, int $companyId): void
    {
        CompanyUser::where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->delete();

        // Fire domain event
        UserRemovedFromCompany::dispatch($user, $companyId);
    }
}