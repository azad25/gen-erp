<?php

namespace App\Console\Commands;

use Database\Seeders\DevSampleDataSeeder;
use Illuminate\Console\Command;

/**
 * Seeds the database with comprehensive test data for developer QA.
 *
 * Creates a dedicated dev admin account with 3 fully-populated BD business scenarios.
 */
class SeedDevSampleData extends Command
{
    protected $signature = 'dev:seed-sample-data {--force : Run even in non-local environments}';

    protected $description = 'Seed comprehensive test data for developer QA (3 BD business scenarios)';

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

        $this->info('🚀 Seeding comprehensive sample data...');
        $this->newLine();

        // First, ensure dev admin exists with correct setup
        $this->info('👤 Setting up dev admin account...');
        $this->call('db:seed', [
            '--class' => 'DevAdminSeeder',
            '--force' => true,
        ]);
        $this->newLine();

        $seeder = new DevSampleDataSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->newLine();
        $this->info('✅ Sample data seeded successfully!');
        $this->newLine();
        $this->table(['Field', 'Value'], [
            ['Dev Admin Email', 'dev@generp.test'],
            ['Dev Admin Password', 'DevAdmin@123'],
            ['Companies Created', '3+'],
            ['Scenarios', 'Ruposhi Retail, Shifa Pharmacy, Apex Garments'],
        ]);

        return self::SUCCESS;
    }
}
