<?php

namespace App\Console\Commands;

use Database\Seeders\DevSampleDataSeeder;
use Illuminate\Console\Command;

/**
 * Seeds the database with comprehensive test data for developer QA.
 *
 * Creates a dedicated dev admin account with 3 fully-populated BD business scenarios.
 * 
 * COMPREHENSIVE COVERAGE:
 * - ALL 29 domains seeded with realistic data
 * - 10,000+ records across all business entities
 * - 3 different business scenarios (Retail, Pharmacy, Manufacturing)
 * - Complete workflows, forms, CRM, projects, POS, payments, logistics, etc.
 */
class SeedDevSampleData extends Command
{
    protected $signature = 'dev:seed-sample-data {--force : Run even in non-local environments}';

    protected $description = 'Seed comprehensive test data for ALL DOMAINS - 29 domains with 10,000+ records (3 BD business scenarios)';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Cannot run in production. Use --force to override.');

            return self::FAILURE;
        }

        if (! app()->environment('local', 'testing') && ! $this->option('force')) {
            if (! $this->confirm('You are not in local/testing environment. Continue?')) {
                return self::FAILURE;
            }
        }

        $this->info('🚀 Seeding COMPREHENSIVE sample data for ALL 29 DOMAINS...');
        $this->info('📊 This will create 10,000+ records across all business entities...');
        $this->newLine();

        // First, ensure dev admin exists with correct setup
        $this->info('👤 Setting up dev admin account...');
        $this->call('db:seed', [
            '--class' => 'DevAdminSeeder',
            '--force' => true,
        ]);
        $this->newLine();

        $seeder = new DevSampleDataSeeder;
        $seeder->setCommand($this);
        $seeder->run();

        $this->newLine();
        $this->info('✅ COMPREHENSIVE sample data seeded successfully for ALL DOMAINS!');
        $this->newLine();
        $this->table(['Field', 'Value'], [
            ['Dev Admin Email', 'dev@generp.test'],
            ['Dev Admin Password', 'DevAdmin@123'],
            ['Companies Created', '3 (Comprehensive)'],
            ['Scenarios', 'Ruposhi Retail, Shifa Pharmacy, Apex Garments'],
            ['Domains Covered', 'ALL 29 Domains'],
            ['Total Records', '10,000+ across all domains'],
        ]);

        return self::SUCCESS;
    }
}
