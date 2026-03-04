<?php

namespace Database\Factories\Domain\CRM\Models;

use App\Domain\CRM\Enums\ActivityType;
use App\Domain\CRM\Models\CrmActivity;
use App\Domain\CRM\Models\Lead;
use App\Domain\CRM\Models\Opportunity;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\CRM\Models\CrmActivity>
 */
class CrmActivityFactory extends Factory
{
    protected $model = CrmActivity::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(ActivityType::cases());
        $scheduledAt = $this->faker->boolean(70) ? $this->faker->dateTimeBetween('-7 days', '+30 days') : null;
        
        return [
            'uuid' => $this->faker->uuid(),
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'subject_type' => $this->faker->randomElement([
                'lead',
                'opportunity',
                'customer',
            ]),
            'subject_id' => $this->faker->numberBetween(1, 100),
            'type' => $type,
            'title' => $this->generateTitle($type),
            'description' => $this->faker->boolean(60) ? $this->faker->paragraph() : null,
            'status' => $this->faker->randomElement(['scheduled', 'in_progress', 'completed', 'cancelled']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'scheduled_at' => $scheduledAt,
            'started_at' => null,
            'completed_at' => null,
            'duration_minutes' => null,
            'planned_duration_minutes' => $type->isSchedulable() ? $this->faker->numberBetween(15, 120) : null,
            'direction' => $type === ActivityType::CALL || $type === ActivityType::EMAIL ? 
                $this->faker->randomElement(['inbound', 'outbound']) : null,
            'outcome' => null,
            'outcome_notes' => null,
            'due_date' => $this->faker->boolean(40) ? $this->faker->dateTimeBetween('now', '+14 days') : null,
            'is_reminder' => $this->faker->boolean(20),
            'reminder_at' => $this->faker->boolean(20) ? $this->faker->dateTimeBetween('now', '+7 days') : null,
            'reminder_sent' => false,
            'email_subject' => $type === ActivityType::EMAIL ? $this->faker->sentence() : null,
            'email_body' => $type === ActivityType::EMAIL ? $this->faker->paragraphs(2, true) : null,
            'attachments' => $this->faker->boolean(10) ? [
                ['name' => 'document.pdf', 'path' => '/storage/documents/document.pdf']
            ] : null,
            'meeting_location' => $type === ActivityType::MEETING ? 
                $this->faker->randomElement(['Office', 'Client Location', 'Coffee Shop', 'Online']) : null,
            'meeting_link' => $type === ActivityType::MEETING && $this->faker->boolean(50) ? 
                'https://meet.google.com/' . $this->faker->uuid() : null,
            'attendees' => $type === ActivityType::MEETING ? [
                ['name' => $this->faker->name(), 'email' => $this->faker->email()]
            ] : null,
            'custom_fields' => null,
            'metadata' => null,
        ];
    }

    private function generateTitle(ActivityType $type): string
    {
        return match($type) {
            ActivityType::CALL => $this->faker->randomElement([
                'Follow-up call',
                'Discovery call',
                'Demo call',
                'Closing call'
            ]),
            ActivityType::EMAIL => $this->faker->randomElement([
                'Send proposal',
                'Follow-up email',
                'Introduction email',
                'Thank you email'
            ]),
            ActivityType::MEETING => $this->faker->randomElement([
                'Product demo',
                'Discovery meeting',
                'Proposal presentation',
                'Contract discussion'
            ]),
            ActivityType::TASK => $this->faker->randomElement([
                'Prepare proposal',
                'Research company',
                'Update CRM',
                'Send contract'
            ]),
            ActivityType::NOTE => $this->faker->randomElement([
                'Meeting notes',
                'Call summary',
                'Important update',
                'Client feedback'
            ]),
            default => $type->label() . ' activity'
        };
    }

    public function call(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ActivityType::CALL,
            'title' => $this->faker->randomElement(['Follow-up call', 'Discovery call', 'Demo call']),
            'planned_duration_minutes' => $this->faker->numberBetween(15, 60),
            'direction' => $this->faker->randomElement(['inbound', 'outbound']),
        ]);
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ActivityType::EMAIL,
            'title' => $this->faker->randomElement(['Send proposal', 'Follow-up email', 'Introduction email']),
            'email_subject' => $this->faker->sentence(),
            'email_body' => $this->faker->paragraphs(2, true),
            'direction' => $this->faker->randomElement(['inbound', 'outbound']),
        ]);
    }

    public function meeting(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ActivityType::MEETING,
            'title' => $this->faker->randomElement(['Product demo', 'Discovery meeting', 'Proposal presentation']),
            'planned_duration_minutes' => $this->faker->numberBetween(30, 120),
            'meeting_location' => $this->faker->randomElement(['Office', 'Client Location', 'Online']),
            'meeting_link' => $this->faker->boolean(60) ? 'https://meet.google.com/' . $this->faker->uuid() : null,
            'attendees' => [
                ['name' => $this->faker->name(), 'email' => $this->faker->email()]
            ],
        ]);
    }

    public function completed(): static
    {
        $completedAt = $this->faker->dateTimeBetween('-30 days', 'now');
        $startedAt = $this->faker->dateTimeBetween($completedAt->format('Y-m-d H:i:s') . ' -2 hours', $completedAt);
        
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'duration_minutes' => $startedAt->diffInMinutes($completedAt),
            'outcome' => $this->faker->randomElement(['successful', 'no_answer', 'busy', 'rescheduled']),
            'outcome_notes' => $this->faker->sentence(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+30 days'),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'due_date' => $this->faker->dateTimeBetween('-7 days', '-1 day'),
        ]);
    }

    public function forLead(Lead $lead): static
    {
        return $this->state(fn (array $attributes) => [
            'subject_type' => 'lead',
            'subject_id' => $lead->id,
            'company_id' => $lead->company_id,
        ]);
    }

    public function forOpportunity(Opportunity $opportunity): static
    {
        return $this->state(fn (array $attributes) => [
            'subject_type' => 'opportunity',
            'subject_id' => $opportunity->id,
            'company_id' => $opportunity->company_id,
        ]);
    }
}