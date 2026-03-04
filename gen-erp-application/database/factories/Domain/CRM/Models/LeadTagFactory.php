<?php

namespace Database\Factories\Domain\CRM\Models;

use App\Domain\CRM\Models\LeadTag;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\CRM\Models\LeadTag>
 */
class LeadTagFactory extends Factory
{
    protected $model = LeadTag::class;

    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Hot Lead', 'Cold Lead', 'Warm Lead', 'VIP', 'Enterprise',
            'SMB', 'Startup', 'High Priority', 'Follow Up', 'Demo Scheduled',
            'Proposal Sent', 'Negotiating', 'Decision Maker', 'Budget Approved',
            'Technical Evaluation', 'Competitor', 'Referral', 'Marketing Qualified',
            'Sales Qualified', 'Product Interest'
        ]);

        return [
            'uuid' => $this->faker->uuid(),
            'company_id' => Company::factory(),
            'created_by' => User::factory(),
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'description' => $this->faker->boolean(60) ? $this->faker->sentence() : null,
            'color' => $this->faker->randomElement([
                '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16',
                '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9',
                '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
                '#ec4899', '#f43f5e'
            ]),
            'icon' => $this->faker->randomElement([
                'tag', 'star', 'flag', 'bookmark', 'heart', 'fire',
                'lightning', 'shield', 'crown', 'diamond'
            ]),
            'is_active' => $this->faker->boolean(90),
            'is_system' => $this->faker->boolean(20),
            'category' => $this->faker->randomElement([
                'priority', 'source', 'industry', 'size', 'status', 'custom'
            ]),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'usage_count' => $this->faker->numberBetween(0, 50),
            'last_used_at' => $this->faker->boolean(70) ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
        ]);
    }

    public function priority(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'priority',
            'name' => $this->faker->randomElement(['High Priority', 'Medium Priority', 'Low Priority']),
            'color' => $this->faker->randomElement(['#ef4444', '#f59e0b', '#22c55e']),
        ]);
    }

    public function source(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'source',
            'name' => $this->faker->randomElement(['Website', 'Referral', 'Social Media', 'Cold Call']),
            'color' => $this->faker->randomElement(['#3b82f6', '#8b5cf6', '#ec4899', '#f97316']),
        ]);
    }
}