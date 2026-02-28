<?php

use App\Models\Company;
use App\Services\BusinessTypeTemplateService;
use App\Services\CompanyContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

if (! function_exists('__entity')) {
    /**
     * Resolve an entity alias for the active company.
     *
     * Resolution order:
     * 1. Check entity_aliases for active company (cached in Redis, 60-min TTL)
     * 2. Fall back to BusinessType static defaults
     * 3. Fall back to Str::title($key)
     */
    function __entity(string $key, ?Company $company = null): string
    {
        try {
            $company = $company ?? CompanyContext::active();
        } catch (\App\Exceptions\NoActiveCompanyException $e) {
            return Str::title(str_replace('_', ' ', $key));
        }

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

        if (isset($aliases[$key])) {
            return $aliases[$key];
        }

        // Fall back to business-type static defaults
        $businessType = $company->business_type instanceof \App\Enums\BusinessType
            ? $company->business_type->value
            : $company->business_type;

        $typeDefaults = BusinessTypeTemplateService::aliasDefaults()[$businessType] ?? [];

        if (isset($typeDefaults[$key])) {
            return $typeDefaults[$key];
        }

        // Fall back to title-cased key
        return Str::title(str_replace('_', ' ', $key));
    }
}
