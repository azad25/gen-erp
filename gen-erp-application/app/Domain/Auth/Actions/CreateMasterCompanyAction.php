<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Support\Str;

/**
 * Domain action for creating a master company during user registration.
 */
class CreateMasterCompanyAction
{
    /**
     * Create a master company for a user with minimal data.
     */
    public function execute(User $user, string $companyName): Company
    {
        $company = Company::create([
            'uuid' => Str::uuid(),
            'name' => $companyName,
            'slug' => Str::slug($companyName),
            'email' => $user->email,
            'country' => 'BD',
            'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka',
            'locale' => 'en',
            'business_type' => 'other', // Default, can be updated later
            'is_active' => true,
            'is_master_company' => true,
            'company_type' => 'master',
            'show_aggregated_data' => false, // Can be enabled later when subsidiaries are added
            'plan' => 'free', // Default plan
        ]);

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