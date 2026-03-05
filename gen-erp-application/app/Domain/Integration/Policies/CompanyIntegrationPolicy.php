<?php

namespace App\Domain\Integration\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\Integration\Models\CompanyIntegration;

class CompanyIntegrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view-integrations');
    }

    public function view(User $user, CompanyIntegration $companyIntegration): bool
    {
        return $user->company_id === $companyIntegration->company_id
            && $user->hasPermission('view-integrations');
    }

    public function update(User $user, CompanyIntegration $companyIntegration): bool
    {
        return $user->company_id === $companyIntegration->company_id
            && $user->hasPermission('update-integrations');
    }

    public function delete(User $user, CompanyIntegration $companyIntegration): bool
    {
        return $user->company_id === $companyIntegration->company_id
            && $user->hasPermission('delete-integrations');
    }
}
