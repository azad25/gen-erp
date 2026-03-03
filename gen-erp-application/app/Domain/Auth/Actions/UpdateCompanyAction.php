<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTOs\UpdateCompanyData;
use App\Domain\Auth\Events\CompanyUpdated;
use App\Domain\Auth\Models\Company;

/**
 * Update an existing company.
 */
class UpdateCompanyAction
{
    public function execute(Company $company, UpdateCompanyData $data): Company
    {
        $oldData = $company->toArray();
        
        $company->update($data->toArray());

        // Fire domain event
        CompanyUpdated::dispatch($company, $oldData);

        return $company->fresh();
    }
}