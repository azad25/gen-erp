<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Seeds standard units of measure for a company.
 */
class DefaultUnitsSeeder extends Seeder
{
    /**
     * @return array<int, array{name: string, abbreviation: string}>
     */
    public static function defaultUnits(): array
    {
        return [
            // General
            ['name' => 'Pieces', 'abbreviation' => 'pcs'],
            ['name' => 'Box', 'abbreviation' => 'box'],
            ['name' => 'Dozen', 'abbreviation' => 'doz'],
            ['name' => 'Pair', 'abbreviation' => 'pair'],
            ['name' => 'Set', 'abbreviation' => 'set'],
            // Weight
            ['name' => 'Kilogram', 'abbreviation' => 'kg'],
            ['name' => 'Gram', 'abbreviation' => 'g'],
            ['name' => 'Tonne', 'abbreviation' => 'tonne'],
            // Volume
            ['name' => 'Litre', 'abbreviation' => 'L'],
            ['name' => 'Millilitre', 'abbreviation' => 'mL'],
            // Length
            ['name' => 'Metre', 'abbreviation' => 'm'],
            ['name' => 'Centimetre', 'abbreviation' => 'cm'],
            ['name' => 'Yard', 'abbreviation' => 'yd'],
            ['name' => 'Feet', 'abbreviation' => 'ft'],
        ];
    }

    /**
     * Seed default units for a specific company.
     */
    public function seedForCompany(Company $company): void
    {
        foreach (self::defaultUnits() as $unit) {
            Unit::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $company->id, 'abbreviation' => $unit['abbreviation']],
                ['name' => $unit['name']]
            );
        }
    }

    /**
     * Seed for all companies (for artisan db:seed usage).
     */
    public function run(): void
    {
        foreach (Company::withoutGlobalScopes()->get() as $company) {
            $this->seedForCompany($company);
        }
    }
}
