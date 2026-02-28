<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'monthly_price' => 0,
                'annual_price' => 0,
                'limits' => [
                    'products' => 50,
                    'users' => 2,
                    'branches' => 1,
                    'storage_bytes' => 52428800,     // 50 MB
                    'integrations' => 0,
                    'custom_fields' => 3,
                ],
                'feature_flags' => [
                    'api_access' => false,
                    'plugin_sdk' => false,
                    'report_export' => false,
                    'audit_log' => false,
                    'multi_branch' => false,
                    'custom_branding' => false,
                ],
                'description' => 'Get started for free. Perfect for small shops getting organized.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'monthly_price' => 99900,   // ৳999/month
                'annual_price' => 999900,   // ৳9,999/year (2 months free)
                'limits' => [
                    'products' => -1,        // Unlimited
                    'users' => 10,
                    'branches' => 5,
                    'storage_bytes' => 1073741824,   // 1 GB
                    'integrations' => 5,
                    'custom_fields' => 20,
                ],
                'feature_flags' => [
                    'api_access' => true,
                    'plugin_sdk' => false,
                    'report_export' => true,
                    'audit_log' => true,
                    'multi_branch' => true,
                    'custom_branding' => true,
                ],
                'description' => 'For growing businesses. Unlimited products, API access, and integrations.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'monthly_price' => 249900,  // ৳2,499/month
                'annual_price' => 2499900,  // ৳24,999/year (2 months free)
                'limits' => [
                    'products' => -1,
                    'users' => -1,
                    'branches' => -1,
                    'storage_bytes' => 5368709120,   // 5 GB
                    'integrations' => -1,
                    'custom_fields' => -1,
                ],
                'feature_flags' => [
                    'api_access' => true,
                    'plugin_sdk' => true,
                    'report_export' => true,
                    'audit_log' => true,
                    'multi_branch' => true,
                    'custom_branding' => true,
                ],
                'description' => 'For large enterprises. Everything unlimited, plus Plugin SDK and priority support.',
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData,
            );
        }
    }
}
