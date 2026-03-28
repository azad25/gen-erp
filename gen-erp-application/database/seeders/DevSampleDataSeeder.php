<?php

namespace Database\Seeders;

use App\Support\Enums\BusinessType;
use App\Support\Enums\CompanyRole;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\CompanyUser;
use App\Domain\Auth\Models\User;
use Database\Seeders\SampleData\ApexGarmentsSeeder;
use Database\Seeders\SampleData\RuposhiRetailSeeder;
use Database\Seeders\SampleData\ShifaPharmacySeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Master seeder creating a dev admin account with 3 comprehensive BD business scenarios.
 */
class DevSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Create/Find Dev Admin ────────────────────────

        $devAdmin = User::firstOrCreate(
            ['email' => 'dev@generp.test'],
            [
                'name' => 'Dev Admin',
                'password' => Hash::make('DevAdmin@123'),
                'email_verified_at' => now(),
                'phone' => '01700000000',
            ],
        );

        // ── 2. Seed Plans ──────────────────────────────────

        $this->call(PlanSeeder::class);

        // ── 3. Seed Integrations (Native Tier 1) ───────────

        $this->call(IntegrationSeeder::class);

        $this->command?->info("👤 Dev admin: {$devAdmin->email}");

        // Get the dev master company (should exist from DevAdminSeeder)
        $devMasterCompany = Company::where('slug', 'dev-master-company')->first();
        
        if (!$devMasterCompany) {
            $this->command?->error('Dev Master Company not found! Make sure DevAdminSeeder ran first.');
            return;
        }
        
        $this->command?->info("🏢 Using Dev Master Company: {$devMasterCompany->name}");

        // ── 4. Ruposhi Retail (Subsidiary) ─────────────────

        $this->command?->info('🏪 Seeding Ruposhi Retail...');
        $ruposhi = $this->createSubsidiaryCompany(
            'Ruposhi Retail',
            BusinessType::RETAIL,
            $devMasterCompany,
            $devAdmin,
            vatBin: '123456789012',
        );
        $ruposhiSeeder = new RuposhiRetailSeeder;
        $ruposhiSeeder->setCommand($this->command);
        $ruposhiSeeder->run($ruposhi, $devAdmin);

        // ── 5. Shifa Pharmacy (Subsidiary) ──────────────────────────────

        $this->command?->info('💊 Seeding Shifa Pharmacy...');
        $shifa = $this->createSubsidiaryCompany(
            'Shifa Pharmacy',
            BusinessType::PHARMACY,
            $devMasterCompany,
            $devAdmin,
            vatBin: '234567890123',
        );
        $shifaSeeder = new ShifaPharmacySeeder;
        $shifaSeeder->setCommand($this->command);
        $shifaSeeder->run($shifa, $devAdmin);

        // ── 6. Apex Garments (Subsidiary) ───────────────────────────────

        $this->command?->info('🏭 Seeding Apex Garments...');
        $apex = $this->createSubsidiaryCompany(
            'Apex Garments Ltd',
            BusinessType::MANUFACTURING,
            $devMasterCompany,
            $devAdmin,
            vatBin: '345678901234',
        );
        $apexSeeder = new ApexGarmentsSeeder;
        $apexSeeder->setCommand($this->command);
        $apexSeeder->run($apex, $devAdmin);
    }

    /**
     * Create a subsidiary company under a master company.
     */
    private function createSubsidiaryCompany(
        string $name,
        BusinessType $businessType,
        Company $parentCompany,
        User $owner,
        string $vatBin = '',
    ): Company {
        $company = Company::firstOrCreate(
            ['slug' => Str::slug($name)],
            [
                'uuid' => Str::uuid(),
                'name' => $name,
                'slug' => Str::slug($name),
                'parent_company_id' => $parentCompany->id,
                'is_master_company' => false,
                'company_type' => 'subsidiary',
                'business_type' => $businessType,
                'country' => 'BD',
                'currency' => 'BDT',
                'timezone' => 'Asia/Dhaka',
                'locale' => 'en',
                'vat_registered' => ! empty($vatBin),
                'vat_bin' => $vatBin ?: null,
                'address_line1' => 'Mirpur Road',
                'city' => 'Dhaka',
                'district' => 'Dhaka',
                'postal_code' => '1205',
                'phone' => '01712000000',
                'email' => strtolower(Str::slug($name, '.')).'@example.com',
                'is_active' => true,
                'plan' => 'enterprise',
                'onboarding_completed_at' => now(),
            ],
        );

        // Attach owner if not already
        CompanyUser::firstOrCreate(
            ['company_id' => $company->id, 'user_id' => $owner->id],
            [
                'role' => CompanyRole::OWNER->value,
                'is_owner' => true,
                'is_active' => true,
                'joined_at' => now(),
            ],
        );

        // Seed default tax groups
        TaxGroupSeeder::createForCompany($company->id);

        return $company;
    }

    /**
     * Create a master company with the dev admin as owner.
     */
    private function createCompany(
        string $name,
        BusinessType $businessType,
        User $owner,
        string $vatBin = '',
    ): Company {
        $company = Company::firstOrCreate(
            ['slug' => Str::slug($name)],
            [
                'uuid' => Str::uuid(),
                'name' => $name,
                'slug' => Str::slug($name),
                'is_master_company' => true,
                'company_type' => 'master',
                'business_type' => $businessType,
                'country' => 'BD',
                'currency' => 'BDT',
                'timezone' => 'Asia/Dhaka',
                'locale' => 'en',
                'vat_registered' => ! empty($vatBin),
                'vat_bin' => $vatBin ?: null,
                'address_line1' => 'Mirpur Road',
                'city' => 'Dhaka',
                'district' => 'Dhaka',
                'postal_code' => '1205',
                'phone' => '01712000000',
                'email' => strtolower(Str::slug($name, '.')).'@example.com',
                'is_active' => true,
                'plan' => 'enterprise',
                'onboarding_completed_at' => now(),
            ],
        );

        // Attach owner if not already
        CompanyUser::firstOrCreate(
            ['company_id' => $company->id, 'user_id' => $owner->id],
            [
                'role' => CompanyRole::OWNER->value,
                'is_owner' => true,
                'is_active' => true,
                'joined_at' => now(),
            ],
        );

        // Seed default tax groups
        TaxGroupSeeder::createForCompany($company->id);

        return $company;
    }
}
