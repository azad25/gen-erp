<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTOs\CompanyMembershipData;
use App\Domain\Auth\Events\UserAddedToCompany;
use App\Domain\Auth\Models\CompanyUser;
use App\Domain\Auth\Models\User;

/**
 * Add a user to a company with specific role and permissions.
 */
class AddUserToCompanyAction
{
    public function execute(User $user, CompanyMembershipData $membershipData): void
    {
        CompanyUser::updateOrCreate(
            [
                'user_id' => $user->id,
                'company_id' => $membershipData->companyId,
            ],
            $membershipData->toArray()
        );

        // Fire domain event
        UserAddedToCompany::dispatch($user, $membershipData->companyId, $membershipData->role);
    }
}