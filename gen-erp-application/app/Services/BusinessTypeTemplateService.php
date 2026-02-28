<?php

namespace App\Services;

use App\Enums\BusinessType;
use App\Models\Company;
use App\Models\EntityAlias;

/**
 * Applies business-type-specific defaults (entity aliases, settings) to a newly created company.
 */
class BusinessTypeTemplateService
{
    /**
     * Static mapping of business-type-specific entity aliases.
     *
     * @return array<string, array<string, string>>
     */
    public static function aliasDefaults(): array
    {
        return [
            BusinessType::PHARMACY->value => [
                'customer' => 'Patient',
                'product' => 'Medicine',
                'supplier' => 'Distributor',
            ],
            BusinessType::SCHOOL->value => [
                'customer' => 'Student',
                'product' => 'Fee Head',
                'supplier' => 'Vendor',
            ],
            BusinessType::RMG->value => [
                'customer' => 'Buyer',
                'order' => 'Work Order',
                'product' => 'Style',
            ],
            BusinessType::RESTAURANT->value => [
                'product' => 'Menu Item',
                'purchase_order' => 'Requisition',
            ],
            BusinessType::NGO->value => [
                'customer' => 'Beneficiary',
                'invoice' => 'Grant Receipt',
            ],
        ];
    }

    /**
     * Apply all business type defaults to the given company.
     */
    public function apply(Company $company): void
    {
        $this->applyEntityAliases($company);
        $this->applyDefaultSettings($company);
        // TODO: Phase 2 — applyDefaultCustomFields, applyDefaultWorkflows, applyDefaultAlertRules
    }

    /**
     * Create EntityAlias records based on the company's business type.
     */
    private function applyEntityAliases(Company $company): void
    {
        $businessType = $company->business_type instanceof BusinessType
            ? $company->business_type->value
            : $company->business_type;

        $aliases = self::aliasDefaults()[$businessType] ?? [];

        foreach ($aliases as $entityKey => $alias) {
            EntityAlias::withoutGlobalScopes()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'entity_key' => $entityKey,
                ],
                [
                    'alias' => $alias,
                ]
            );
        }
    }

    /**
     * Set default company settings JSON based on business type.
     */
    private function applyDefaultSettings(Company $company): void
    {
        $businessType = $company->business_type instanceof BusinessType
            ? $company->business_type
            : BusinessType::from($company->business_type);

        $settings = [
            'simplified_mode' => $businessType->simplifiedModeDefault(),
            'invoice_prefix' => 'INV',
            'po_prefix' => 'PO',
            'date_format' => 'd M Y',
            'time_format' => 'h:i A',
            'fiscal_year_start' => '07-01',
        ];

        $company->update(['settings' => $settings]);
    }
}
