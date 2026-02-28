<?php

namespace Database\Seeders;

use App\Enums\BusinessType;
use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
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

        $this->command?->info("👤 Dev admin: {$devAdmin->email}");

        // ── 2. Seed Plans ──────────────────────────────────

        $this->call(PlanSeeder::class);

        // ── 3. Ruposhi Retail (Retail Shop) ─────────────────

        $this->command?->info('🏪 Seeding Ruposhi Retail...');
        $ruposhi = $this->createCompany(
            'Ruposhi Retail',
            BusinessType::RETAIL,
            $devAdmin,
            vatBin: '123456789012',
        );
        (new RuposhiRetailSeeder())->run($ruposhi, $devAdmin);

        // ── 4. Shifa Pharmacy ──────────────────────────────

        $this->command?->info('💊 Seeding Shifa Pharmacy...');
        $shifa = $this->createCompany(
            'Shifa Pharmacy',
            BusinessType::PHARMACY,
            $devAdmin,
            vatBin: '234567890123',
        );
        (new ShifaPharmacySeeder())->run($shifa, $devAdmin);

        // ── 5. Apex Garments ───────────────────────────────

        $this->command?->info('🏭 Seeding Apex Garments...');
        $apex = $this->createCompany(
            'Apex Garments Ltd',
            BusinessType::MANUFACTURING,
            $devAdmin,
            vatBin: '345678901234',
        );
        (new ApexGarmentsSeeder())->run($apex, $devAdmin);
    }

    /**
     * Create a company with the dev admin as owner.
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
