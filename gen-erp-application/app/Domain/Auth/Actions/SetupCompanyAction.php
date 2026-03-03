<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DataTransferObjects\CompanySetupData;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;

/**
 * Domain action for company setup.
 */
class SetupCompanyAction
{
    /**
     * Execute company setup for a user.
     */
    public function execute(User $user, CompanySetupData $data): Company
    {
        // Create company
        $companyData = $data->toArray();
        $companyData['uuid'] = $data->getUuid();
        $companyData['slug'] = $data->getSlug();

        // Use user's email if company email not provided
        if (empty($companyData['email'])) {
            $companyData['email'] = $user->email;
        }

        $company = Company::create($companyData);

        // Attach user to company as owner
        $company->users()->attach($user->id, [
            'role' => 'owner',
            'is_owner' => true,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        // Update user's last active company
        $user->update(['last_active_company_id' => $company->id]);

        return $company;
    }
}
