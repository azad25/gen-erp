<?php

namespace Tests\Feature\Domain\CRM;

use App\Domain\CRM\Models\Lead;
use App\Domain\CRM\Models\Opportunity;
use App\Domain\CRM\Models\Pipeline;
use App\Domain\CRM\Models\PipelineStage;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OpportunityApiTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private Pipeline $pipeline;
    private PipelineStage $stage;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set locale to English for consistent test assertions
        app()->setLocale('en');
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        
        // Associate user with company and set as current company
        $this->user->companies()->attach($this->company->id, ['role' => 'admin']);
        $this->user->update(['current_company_id' => $this->company->id]);
        
        // Create pipeline and stage
        $this->pipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
        
        $this->stage = PipelineStage::factory()->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $this->pipeline->id,
            'created_by' => $this->user->id,
            'probability' => 25,
        ]);
        
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_opportunities(): void
    {
        Opportunity::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
        ]);

        $response = $this->getJson('/api/v1/crm/opportunities');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'name',
                        'description',
                        'amount',
                        'currency',
                        'status',
                        'probability',
                        'pipeline',
                        'stage',
                        'created_at',
                    ]
                ],
                'links',
                'meta',
            ]);

        $this->assertEquals(3, $response->json('meta.total'));
    }

    public function test_can_create_opportunity(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $opportunityData = [
            'name' => 'Test Opportunity',
            'description' => 'Test opportunity description',
            'amount' => 50000.00,
            'currency' => 'BDT',
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'lead_id' => $lead->id,
            'expected_close_date' => now()->addDays(30)->format('Y-m-d'),
            'probability' => 30,
        ];

        $response = $this->postJson('/api/v1/crm/opportunities', $opportunityData);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'name',
                    'description',
                    'amount',
                    'currency',
                    'status',
                    'probability',
                    'pipeline',
                    'stage',
                    'lead',
                ]
            ]);

        $this->assertDatabaseHas('opportunities', [
            'name' => 'Test Opportunity',
            'amount' => 50000.00,
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'lead_id' => $lead->id,
        ]);
    }

    public function test_create_opportunity_validation(): void
    {
        $response = $this->postJson('/api/v1/crm/opportunities', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'amount', 'pipeline_id', 'stage_id']);
    }

    public function test_can_show_opportunity(): void
    {
        $opportunity = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
        ]);

        $response = $this->getJson("/api/v1/crm/opportunities/{$opportunity->uuid}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'name',
                    'description',
                    'amount',
                    'currency',
                    'status',
                    'probability',
                    'pipeline',
                    'stage',
                    'created_by',
                ]
            ]);

        $this->assertEquals($opportunity->uuid, $response->json('data.uuid'));
    }

    public function test_cannot_show_opportunity_from_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $opportunity = Opportunity::factory()->create([
            'company_id' => $otherCompany->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
        ]);

        $response = $this->getJson("/api/v1/crm/opportunities/{$opportunity->uuid}");

        $response->assertNotFound();
    }

    public function test_can_update_opportunity(): void
    {
        $opportunity = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'name' => 'Original Name',
        ]);

        $updateData = [
            'name' => 'Updated Opportunity',
            'description' => 'Updated description',
            'amount' => 75000.00,
            'probability' => 50,
        ];

        $response = $this->putJson("/api/v1/crm/opportunities/{$opportunity->uuid}", $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'name',
                    'description',
                    'amount',
                    'probability',
                ]
            ]);

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'name' => 'Updated Opportunity',
            'description' => 'Updated description',
            'amount' => 75000.00,
            'probability' => 50,
        ]);
    }

    public function test_can_delete_opportunity(): void
    {
        $opportunity = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
        ]);

        $response = $this->deleteJson("/api/v1/crm/opportunities/{$opportunity->uuid}");

        $response->assertOk()
            ->assertJson(['message' => __('crm.opportunity_deleted_successfully')]);

        $this->assertSoftDeleted('opportunities', ['id' => $opportunity->id]);
    }

    public function test_can_move_opportunity_to_stage(): void
    {
        $newStage = PipelineStage::factory()->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $this->pipeline->id,
            'created_by' => $this->user->id,
            'probability' => 75,
        ]);

        $opportunity = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
        ]);

        $response = $this->postJson("/api/v1/crm/opportunities/{$opportunity->uuid}/move-to-stage", [
            'stage_id' => $newStage->id,
            'reason' => 'Moving to next stage',
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.opportunity_moved_successfully')]);

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'stage_id' => $newStage->id,
            'probability' => 75,
        ]);
    }

    public function test_can_mark_opportunity_as_won(): void
    {
        $opportunity = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'status' => 'open',
        ]);

        $response = $this->postJson("/api/v1/crm/opportunities/{$opportunity->uuid}/mark-as-won", [
            'reason' => 'Deal closed successfully',
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.opportunity_won_successfully')]);

        $opportunity->refresh();
        $this->assertEquals('won', $opportunity->status);
        $this->assertNotNull($opportunity->won_at);
        $this->assertEquals('Deal closed successfully', $opportunity->won_reason);
    }

    public function test_can_mark_opportunity_as_lost(): void
    {
        $opportunity = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'status' => 'open',
        ]);

        $response = $this->postJson("/api/v1/crm/opportunities/{$opportunity->uuid}/mark-as-lost", [
            'reason' => 'Budget constraints',
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.opportunity_lost_successfully')]);

        $opportunity->refresh();
        $this->assertEquals('lost', $opportunity->status);
        $this->assertNotNull($opportunity->lost_at);
        $this->assertEquals('Budget constraints', $opportunity->lost_reason);
    }

    public function test_can_assign_opportunity(): void
    {
        $assignee = User::factory()->create();
        $assignee->companies()->attach($this->company->id, ['role' => 'user']);
        
        $opportunity = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'assigned_to' => null,
        ]);

        $response = $this->postJson("/api/v1/crm/opportunities/{$opportunity->uuid}/assign", [
            'assigned_to' => $assignee->id,
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.opportunity_assigned_successfully')]);

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'assigned_to' => $assignee->id,
        ]);
    }

    public function test_can_update_opportunity_probability(): void
    {
        $opportunity = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'probability' => 25,
        ]);

        $response = $this->postJson("/api/v1/crm/opportunities/{$opportunity->uuid}/update-probability", [
            'probability' => 85,
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.opportunity_updated_successfully')]);

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'probability' => 85,
        ]);
    }

    public function test_probability_validation(): void
    {
        $opportunity = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
        ]);

        // Test invalid probability (too high)
        $response = $this->postJson("/api/v1/crm/opportunities/{$opportunity->uuid}/update-probability", [
            'probability' => 150,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['probability']);

        // Test invalid probability (negative)
        $response = $this->postJson("/api/v1/crm/opportunities/{$opportunity->uuid}/update-probability", [
            'probability' => -10,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['probability']);
    }

    public function test_can_bulk_move_to_stage(): void
    {
        $newStage = PipelineStage::factory()->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $this->pipeline->id,
            'created_by' => $this->user->id,
            'probability' => 75,
        ]);

        $opportunities = Opportunity::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
        ]);

        $response = $this->postJson('/api/v1/crm/opportunities/bulk-move-to-stage', [
            'opportunity_ids' => $opportunities->pluck('id')->toArray(),
            'stage_id' => $newStage->id,
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.opportunities_moved_successfully', ['count' => 3])]);

        foreach ($opportunities as $opportunity) {
            $this->assertDatabaseHas('opportunities', [
                'id' => $opportunity->id,
                'stage_id' => $newStage->id,
                'probability' => 75,
            ]);
        }
    }

    public function test_can_bulk_assign_opportunities(): void
    {
        $assignee = User::factory()->create();
        $assignee->companies()->attach($this->company->id, ['role' => 'user']);
        
        $opportunities = Opportunity::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'assigned_to' => null,
        ]);

        $response = $this->postJson('/api/v1/crm/opportunities/bulk-assign', [
            'opportunity_ids' => $opportunities->pluck('id')->toArray(),
            'assigned_to' => $assignee->id,
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.opportunities_assigned_successfully', ['count' => 3])]);

        foreach ($opportunities as $opportunity) {
            $this->assertDatabaseHas('opportunities', [
                'id' => $opportunity->id,
                'assigned_to' => $assignee->id,
            ]);
        }
    }

    public function test_can_get_opportunity_statistics(): void
    {
        // Create opportunities with different statuses
        Opportunity::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'status' => 'open',
            'amount' => 50000,
        ]);

        Opportunity::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'status' => 'won',
            'amount' => 75000,
        ]);

        Opportunity::factory()->count(1)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'status' => 'lost',
            'amount' => 25000,
        ]);

        $response = $this->getJson('/api/v1/crm/opportunities/statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_opportunities',
                    'total_value',
                    'won_value',
                    'lost_value',
                    'open_value',
                    'weighted_pipeline_value',
                    'by_status',
                    'by_stage',
                    'conversion_rate',
                    'average_deal_size',
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(6, $data['total_opportunities']);
        $this->assertEquals(325000, $data['total_value']);
        $this->assertEquals(150000, $data['won_value']);
        $this->assertEquals(25000, $data['lost_value']);
        $this->assertEquals(150000, $data['open_value']);
    }

    public function test_can_get_forecast(): void
    {
        // Create opportunities with different probabilities
        Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'status' => 'open',
            'amount' => 50000,
            'probability' => 80,
            'expected_close_date' => now()->addMonth(),
        ]);

        Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'status' => 'open',
            'amount' => 30000,
            'probability' => 40,
            'expected_close_date' => now()->addMonth(),
        ]);

        $response = $this->getJson('/api/v1/crm/opportunities/forecast');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_opportunities',
                    'total_value',
                    'weighted_value',
                    'by_month',
                    'by_probability' => [
                        'high',
                        'medium',
                        'low',
                    ],
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(2, $data['total_opportunities']);
        $this->assertEquals(80000, $data['total_value']);
        $this->assertEquals(52000, $data['weighted_value']); // (50000*0.8) + (30000*0.4)
    }

    public function test_can_filter_opportunities(): void
    {
        // Create opportunities with different attributes
        Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'status' => 'open',
            'amount' => 50000,
        ]);

        Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'status' => 'won',
            'amount' => 75000,
        ]);

        // Test status filter
        $response = $this->getJson('/api/v1/crm/opportunities?status=open');
        $response->assertOk();
        $this->assertEquals(1, $response->json('meta.total'));

        // Test pipeline filter
        $response = $this->getJson("/api/v1/crm/opportunities?pipeline_id={$this->pipeline->id}");
        $response->assertOk();
        $this->assertEquals(2, $response->json('meta.total'));

        // Test amount filter
        $response = $this->getJson('/api/v1/crm/opportunities?min_amount=60000');
        $response->assertOk();
        $this->assertEquals(1, $response->json('meta.total'));
    }
}