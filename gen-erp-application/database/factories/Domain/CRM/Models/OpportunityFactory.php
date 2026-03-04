<?php

namespace Database\Factories\Domain\CRM\Models;

use App\Domain\CRM\Models\Lead;
use App\Domain\CRM\Models\Opportunity;
use App\Domain\CRM\Models\Pipeline;
use App\Domain\CRM\Models\PipelineStage;
use App\Domain\Auth\Models\Company;
use App\Domain\Customer\Models\Customer;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\CRM\Models\Opportunity>
 */
class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 5000, 100000);
        $discountAmount = $this->faker->randomFloat(2, 0, $amount * 0.1);
        $taxAmount = $this->faker->randomFloat(2, 0, $amount * 0.15);
        
        return [
            'uuid' => $this->faker->uuid(),
            'company_id' => Company::factory(),
            'pipeline_id' => Pipeline::factory(),
            'stage_id' => PipelineStage::factory(),
            'lead_id' => $this->faker->boolean(60) ? Lead::factory() : null,
            'customer_id' => $this->faker->boolean(40) ? Customer::factory() : null,
            'assigned_to' => $this->faker->boolean(80) ? User::factory() : null,
            'created_by' => User::factory(),
            'name' => $this->faker->catchPhrase() . ' Deal',
            'description' => $this->faker->paragraph(),
            'amount' => $amount,
            'currency' => 'BDT',
            'probability' => $this->faker->numberBetween(10, 90),
            'expected_close_date' => $this->faker->dateTimeBetween('now', '+6 months'),
            'actual_close_date' => null,
            'status' => 'open',
            'close_reason' => null,
            'stage_order' => $this->faker->numberBetween(1, 6),
            'source' => $this->faker->randomElement(['website', 'referral', 'social_media', 'advertisement', 'cold_call']),
            'campaign' => $this->faker->boolean(30) ? $this->faker->words(2, true) . ' Campaign' : null,
            'products' => $this->faker->boolean(50) ? [
                [
                    'id' => $this->faker->numberBetween(1, 100),
                    'name' => $this->faker->words(2, true),
                    'quantity' => $this->faker->numberBetween(1, 10),
                    'price' => $this->faker->randomFloat(2, 100, 5000),
                ]
            ] : null,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $amount + $taxAmount - $discountAmount,
            'last_activity_at' => $this->faker->boolean(70) ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            'won_at' => null,
            'lost_at' => null,
            'days_in_stage' => $this->faker->numberBetween(1, 30),
            'custom_fields' => $this->faker->boolean(30) ? [
                'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
                'competitor' => $this->faker->company(),
            ] : null,
            'notes' => $this->faker->boolean(40) ? $this->faker->paragraph() : null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'actual_close_date' => null,
            'won_at' => null,
            'lost_at' => null,
            'close_reason' => null,
        ]);
    }

    public function won(): static
    {
        $wonAt = $this->faker->dateTimeBetween('-30 days', 'now');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'won',
            'probability' => 100,
            'actual_close_date' => $wonAt,
            'won_at' => $wonAt,
            'lost_at' => null,
            'close_reason' => $this->faker->randomElement([
                'Best price and solution',
                'Strong relationship',
                'Superior product features',
                'Excellent proposal'
            ]),
        ]);
    }

    public function lost(): static
    {
        $lostAt = $this->faker->dateTimeBetween('-30 days', 'now');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'lost',
            'probability' => 0,
            'actual_close_date' => $lostAt,
            'won_at' => null,
            'lost_at' => $lostAt,
            'close_reason' => $this->faker->randomElement([
                'Price too high',
                'Chose competitor',
                'Budget constraints',
                'Timeline mismatch',
                'No decision made'
            ]),
        ]);
    }

    public function highValue(): static
    {
        $amount = $this->faker->randomFloat(2, 50000, 500000);
        $discountAmount = $this->faker->randomFloat(2, 0, $amount * 0.05);
        $taxAmount = $this->faker->randomFloat(2, 0, $amount * 0.15);
        
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $amount + $taxAmount - $discountAmount,
        ]);
    }

    public function closingSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expected_close_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'probability' => $this->faker->numberBetween(60, 90),
        ]);
    }
}