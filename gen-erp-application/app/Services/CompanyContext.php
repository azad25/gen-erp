<?php

namespace App\Services;

use App\Exceptions\NoActiveCompanyException;
use App\Models\Company;

/**
 * Manages the currently active company context for tenant-scoped operations.
 */
class CompanyContext
{
    /**
     * Set the active company for the current request.
     */
    public static function setActive(Company $company): void
    {
        // Store in session for web requests
        if (session()->isStarted()) {
            session(['active_company_id' => $company->id]);
        }

        // Store as request attribute for API / current request cycle
        app()->instance('active_company', $company);
        app()->instance('active_company_id', $company->id);
    }

    /**
     * Get the currently active company.
     *
     * @throws NoActiveCompanyException
     */
    public static function active(): Company
    {
        // First check the request-level singleton
        if (app()->bound('active_company')) {
            return app('active_company');
        }

        // Then check session
        $companyId = session('active_company_id');

        if ($companyId) {
            $company = Company::find($companyId);

            if ($company) {
                app()->instance('active_company', $company);
                app()->instance('active_company_id', $company->id);

                return $company;
            }
        }

        throw new NoActiveCompanyException;
    }

    /**
     * Get the active company's ID.
     *
     * @throws NoActiveCompanyException
     */
    public static function activeId(): int
    {
        if (app()->bound('active_company_id')) {
            return app('active_company_id');
        }

        return static::active()->id;
    }

    /**
     * Check if an active company is currently set.
     */
    public static function hasActive(): bool
    {
        if (app()->bound('active_company_id')) {
            return true;
        }

        return session()->has('active_company_id') && session('active_company_id') !== null;
    }
}
