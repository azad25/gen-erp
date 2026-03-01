<?php

namespace App\Services;

use App\Models\User;
use App\Models\Company;
use App\Models\CompanyUser;

class UserService
{
    public function addToCompany(User $user, int $companyId, string $role, bool $isOwner = false): void
    {
        CompanyUser::updateOrCreate(
            [
                'user_id' => $user->id,
                'company_id' => $companyId,
            ],
            [
                'role' => $role,
                'is_owner' => $isOwner,
                'is_active' => true,
                'joined_at' => now(),
            ]
        );
    }

    public function removeFromCompany(User $user, int $companyId): void
    {
        CompanyUser::where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->delete();
    }

    public function sendInvitation(array $data): \App\Models\Invitation
    {
        return \App\Models\Invitation::create($data);
    }
}
