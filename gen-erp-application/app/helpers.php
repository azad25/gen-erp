<?php

use App\Models\Company;
use App\Services\BusinessTypeTemplateService;
use App\Services\CompanyContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

if (! function_exists('activeCompany')) {
    /**
     * Get the currently active company.
     *
     * @return Company|null
     */
    function activeCompany(): ?Company
    {
        try {
            return CompanyContext::active();
        } catch (\App\Exceptions\NoActiveCompanyException $e) {
            return null;
        }
    }
}

if (! function_exists('__entity')) {
    /**
     * Resolve an entity alias for the active company.
     *
     * Resolution order:
     * 1. Check entity_aliases for active company (cached in Redis, 60-min TTL)
     * 2. Fall back to BusinessType static defaults
     * 3. Fall back to Str::title($key)
     *
     * @param string $key The entity key (e.g., 'customer', 'invoice')
     * @param bool $plural Whether to return the plural form
     * @param Company|null $company The company context (defaults to active company)
     * @return string The resolved entity name
     */
    function __entity(string $key, bool $plural = false, ?Company $company = null): string
    {
        try {
            $company = $company ?? CompanyContext::active();
        } catch (\App\Exceptions\NoActiveCompanyException $e) {
            $result = Str::title(str_replace('_', ' ', $key));
            return $plural ? Str::plural($result) : $result;
        }

        // Determine the lookup key (append '_plural' if needed)
        $lookupKey = $plural ? "{$key}_plural" : $key;

        // Check cached entity aliases for this company
        $aliases = Cache::remember(
            "entity_aliases:{$company->id}",
            3600, // 60 minutes
            function () use ($company): array {
                return $company->entityAliases()
                    ->withoutGlobalScopes()
                    ->pluck('alias', 'entity_key')
                    ->all();
            }
        );

        if (isset($aliases[$lookupKey])) {
            return $aliases[$lookupKey];
        }

        // Fall back to business-type static defaults
        $businessType = $company->business_type instanceof \App\Enums\BusinessType
            ? $company->business_type->value
            : $company->business_type;

        $typeDefaults = BusinessTypeTemplateService::aliasDefaults()[$businessType] ?? [];

        if (isset($typeDefaults[$lookupKey])) {
            return $typeDefaults[$lookupKey];
        }

        // If plural was requested but not found, try singular and pluralize it
        if ($plural && isset($aliases[$key])) {
            return Str::plural($aliases[$key]);
        }

        if ($plural && isset($typeDefaults[$key])) {
            return Str::plural($typeDefaults[$key]);
        }

        // Fall back to title-cased key
        $result = Str::title(str_replace('_', ' ', $key));
        return $plural ? Str::plural($result) : $result;
    }
}
