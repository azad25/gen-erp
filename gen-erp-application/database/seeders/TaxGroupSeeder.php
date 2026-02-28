<?php

namespace Database\Seeders;

use App\Models\TaxGroup;
use Illuminate\Database\Seeder;

/**
 * Seeds the 7 standard Bangladesh tax groups per company.
 */
class TaxGroupSeeder extends Seeder
{
    public function run(): void
    {
        // This seeder is meant to be called per-company
        // When called from DatabaseSeeder it creates system defaults
        $this->command?->info('TaxGroupSeeder: Use createForCompany() to seed per-company tax groups.');
    }

    /**
     * Create the 7 standard BD tax groups for a given company.
     *
     * @return array<int, TaxGroup>
     */
    public static function createForCompany(int $companyId): array
    {
        $groups = [
            [
                'company_id' => $companyId,
                'name' => 'VAT 15%',
                'rate' => 15.00,
                'rate_basis_points' => 1500,
                'type' => 'vat',
                'is_compound' => false,
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 1,
                'description' => 'Standard VAT rate (15%) applicable to most goods and services',
            ],
            [
                'company_id' => $companyId,
                'name' => 'VAT 10%',
                'rate' => 10.00,
                'rate_basis_points' => 1000,
                'type' => 'vat',
                'is_compound' => false,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 2,
                'description' => 'Reduced VAT rate (10%)',
            ],
            [
                'company_id' => $companyId,
                'name' => 'VAT 7.5%',
                'rate' => 7.50,
                'rate_basis_points' => 750,
                'type' => 'vat',
                'is_compound' => false,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 3,
                'description' => 'Reduced VAT rate (7.5%) for specific goods',
            ],
            [
                'company_id' => $companyId,
                'name' => 'VAT 5%',
                'rate' => 5.00,
                'rate_basis_points' => 500,
                'type' => 'vat',
                'is_compound' => false,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 4,
                'description' => 'Reduced VAT rate (5%) for essential services',
            ],
            [
                'company_id' => $companyId,
                'name' => 'VAT 2%',
                'rate' => 2.00,
                'rate_basis_points' => 200,
                'type' => 'vat',
                'is_compound' => false,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 5,
                'description' => 'Turnover tax rate (2%) for small businesses',
            ],
            [
                'company_id' => $companyId,
                'name' => 'Zero Rated',
                'rate' => 0.00,
                'rate_basis_points' => 0,
                'type' => 'vat',
                'is_compound' => false,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 6,
                'description' => 'Zero-rated VAT — export goods, certain essential items',
            ],
            [
                'company_id' => $companyId,
                'name' => 'Exempt',
                'rate' => 0.00,
                'rate_basis_points' => 0,
                'type' => 'vat',
                'is_compound' => false,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 7,
                'description' => 'VAT exempt goods and services',
            ],
        ];

        $created = [];
        foreach ($groups as $data) {
            $created[] = TaxGroup::updateOrCreate(
                ['company_id' => $data['company_id'], 'name' => $data['name']],
                $data,
            );
        }

        return $created;
    }
}
