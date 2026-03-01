<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if dev admin already exists
        $devAdmin = User::where('email', 'dev@generp.test')->first();

        if (!$devAdmin) {
            $devAdmin = User::create([
                'name' => 'Dev Admin',
                'email' => 'dev@generp.test',
                'password' => Hash::make('DevAdmin@123'),
                'email_verified_at' => now(), // Pre-verified
                'is_superadmin' => true,
                'password_changed_at' => now(),
            ]);

            $this->command->info('✓ Dev Admin user created: dev@generp.test');
        } else {
            // Update password if user exists
            $devAdmin->update([
                'password' => Hash::make('DevAdmin@123'),
                'email_verified_at' => now(),
                'is_superadmin' => true,
            ]);

            $this->command->info('✓ Dev Admin user updated: dev@generp.test');
        }

        // Create or get dev company
        $devCompany = Company::where('name', 'Dev Company')->first();

        if (!$devCompany) {
            $devCompany = Company::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'name' => 'Dev Company',
                'slug' => 'dev-company',
                'email' => 'dev@generp.test',
                'phone' => '+880 1700-000000',
                'address_line1' => 'Dhaka',
                'city' => 'Dhaka',
                'district' => 'Dhaka',
                'country' => 'BD',
                'business_type' => 'service',
                'is_active' => true,
            ]);

            $this->command->info('✓ Dev Company created');
        }

        // Attach dev admin to dev company if not already attached
        if (!$devAdmin->companies()->where('company_id', $devCompany->id)->exists()) {
            $devAdmin->companies()->attach($devCompany->id, [
                'role' => 'owner',
                'is_owner' => true,
                'is_active' => true,
                'joined_at' => now(),
            ]);

            $this->command->info('✓ Dev Admin attached to Dev Company');
        }

        // Set as last active company
        $devAdmin->update(['last_active_company_id' => $devCompany->id]);

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('  Dev Admin Account Ready');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('  Email:    dev@generp.test');
        $this->command->info('  Password: DevAdmin@123');
        $this->command->info('  Company:  Dev Company');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('');
    }
}
