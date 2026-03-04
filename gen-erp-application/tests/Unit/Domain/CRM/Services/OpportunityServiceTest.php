<?php

namespace Tests\Unit\Domain\CRM\Services;

use App\Domain\CRM\Contracts\OpportunityServiceInterface;
use App\Domain\CRM\DTOs\OpportunityData;
use App\Domain\CRM\Models\Lead;
use App\Domain\CRM\Models\Opportunity;
use App\Domain\CRM\Models\Pipeline;
use App\Domain\CRM\Models\PipelineStage;
use App\Domain\CRM\Services\OpportunityService;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityServiceTest extends TestCase
{
    use RefreshDatabase;

    private OpportunityServiceInterface $opportunityService;
    private Company $company;
    private User $user;
    private Pipeline $pipeline;
    private PipelineStage $stage;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->opportunityService = new OpportunityService();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        
        // Associate user with company
        $this->user->companies()->attach($this->company->id, ['role' => 'admin']);
        
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
    }

    public function test_can_create_opportunity(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $opportunityData = new OpportunityData(
            name: 'Test Opportunity',
            description: 'Test opportunity description',
            amount: 50000.00,
            currency: 'BDT',
            pipelineId: $this->pipeline->id,
            stageId: $this->stage->id,
            leadId: $lead->id,
            expectedCloseDate: now()->addDays(30)
        );

        $opportunity = $this->opportunityService->create($opportunityData, $this->company->id, $this->user->id);

        $this->assertInstanceOf(Opportunity::class, $opportunity);
        $this->assertEquals('Test Opportunity', $opportunity->name);
        $this->assertEquals(50000.00, $opportunity->amount);
        $this->assertEquals($this->company->id, $opportunity->company_id);
        $this->assertEquals($this->user->id, $opportunity->created_by);
        $this->assertEquals($this->pipeline->id, $opportunity->pipeline_id);
        $this->assertEquals($this->stage->id, $opportunity->stage_id);
        $this->assertEquals($lead->id, $opportunity->lead_id);
        $this->assertEquals('open', $opportunity->status);
        $this->assertEquals(25, $opportunity->probability); // From stage
    }

    public function test_can_update_opportunity(): void
    {
        $opportunity = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
        ]);

        $opportunityData = new OpportunityData(
            name: 'Updated Opportunity',
            amount: 75000.00,
            pipelineId: $this->pipeline->id,
            stageId: $this->stage->id,
            expectedCloseDate: now()->addDays(30),
            description: 'Updated description',
            probability: 50
        );

        $updatedOpportunity = $this->opportunityService->update($opportunity, $opportunityData);

        $this->assertEquals('Updated Opportunity', $updatedOpportunity->name);
        $this->assertEquals('Updated description', $updatedOpportunity->description);
        $this->assertEquals(75000.00, $updatedOpportunity->amount);
        $this->assertEquals(50, $updatedOpportunity->probability);
    }

    public function test_can_find_opportunity_by_uuid(): void
    {
        $opportunity = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
        ]);

        $foundOpportunity = $this->opportunityService->findByUuid($opportunity->uuid, $this->company->id);

        $this->assertNotNull($foundOpportunity);
        $this->assertEquals($opportunity->id, $foundOpportunity->id);
        $this->assertEquals($opportunity->uuid, $foundOpportunity->uuid);
    }

    public function test_cannot_find_opportunity_from_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $opportunity = Opportunity::factory()->create([
            'company_id' => $otherCompany->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
        ]);

        $foundOpportunity = $this->opportunityService->findByUuid($opportunity->uuid, $this->company->id);

        $this->assertNull($foundOpportunity);
    }

    public function test_can_move_opportunity_to_stage(): void
    {
        $newStage = PipelineStage::factory()->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $this->pipeline->id,
            'created_by' => $this->user->id,
            'probability' => 75,
            'sort_order' => 99, // Use a high sort order to avoid conflicts
        ]);

        $opportunity = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'probability' => 25,
        ]);

        $movedOpportunity = $this->opportunityService->moveToStage($opportunity, $newStage, 'Moving to next stage');

        $this->assertEquals($newStage->id, $movedOpportunity->stage_id);
        $this->assertEquals(75, $movedOpportunity->probability);
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

        $wonOpportunity = $this->opportunityService->markAsWon($opportunity, 'Deal closed successfully');

        $this->assertEquals('won', $wonOpportunity->status);
        $this->assertNotNull($wonOpportunity->won_at);
        $this->assertEquals('Deal closed successfully', $wonOpportunity->won_reason);
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

        $lostOpportunity = $this->opportunityService->markAsLost($opportunity, 'Budget constraints');

        $this->assertEquals('lost', $lostOpportunity->status);
        $this->assertNotNull($lostOpportunity->lost_at);
        $this->assertEquals('Budget constraints', $lostOpportunity->lost_reason);
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

        $assignedOpportunity = $this->opportunityService->assignTo($opportunity, $assignee->id);

        $this->assertEquals($assignee->id, $assignedOpportunity->assigned_to);
    }

    public function test_can_update_probability(): void
    {
        $opportunity = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'probability' => 25,
        ]);

        $updatedOpportunity = $this->opportunityService->updateProbability($opportunity, 85);

        $this->assertEquals(85, $updatedOpportunity->probability);
    }

    public function test_probability_is_clamped_between_0_and_100(): void
    {
        $opportunity = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'probability' => 50,
        ]);

        // Test upper bound
        $updatedOpportunity = $this->opportunityService->updateProbability($opportunity, 150);
        $this->assertEquals(100, $updatedOpportunity->probability);

        // Test lower bound
        $updatedOpportunity = $this->opportunityService->updateProbability($opportunity, -10);
        $this->assertEquals(0, $updatedOpportunity->probability);
    }

    public function test_can_get_opportunities_for_company_with_filters(): void
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
        $openOpportunities = $this->opportunityService->getForCompany($this->company->id, [
            'status' => 'open',
        ]);
        $this->assertEquals(1, $openOpportunities->count());

        // Test amount filter
        $highValueOpportunities = $this->opportunityService->getForCompany($this->company->id, [
            'min_amount' => 60000,
        ]);
        $this->assertEquals(1, $highValueOpportunities->count());
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

        $statistics = $this->opportunityService->getOpportunityStatistics($this->company->id);

        $this->assertEquals(6, $statistics['total_opportunities']);
        $this->assertEquals(325000, $statistics['total_value']); // (3*50000) + (2*75000) + (1*25000)
        $this->assertEquals(150000, $statistics['won_value']); // 2*75000
        $this->assertEquals(25000, $statistics['lost_value']); // 1*25000
        $this->assertEquals(150000, $statistics['open_value']); // 3*50000
        $this->assertEquals(33.33, $statistics['conversion_rate']); // 2/6 * 100
        $this->assertEquals(54166.67, $statistics['average_deal_size']); // 325000/6
    }

    public function test_can_bulk_move_to_stage(): void
    {
        $newStage = PipelineStage::factory()->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $this->pipeline->id,
            'created_by' => $this->user->id,
            'probability' => 75,
            'sort_order' => 99, // Use a high sort order to avoid conflicts
        ]);

        $opportunities = Opportunity::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
        ]);

        $opportunityIds = $opportunities->pluck('id')->toArray();
        $updated = $this->opportunityService->bulkMoveToStage($opportunityIds, $newStage->id, $this->company->id);

        $this->assertEquals(3, $updated);
        
        foreach ($opportunities as $opportunity) {
            $opportunity->refresh();
            $this->assertEquals($newStage->id, $opportunity->stage_id);
            $this->assertEquals(75, $opportunity->probability);
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

        $opportunityIds = $opportunities->pluck('id')->toArray();
        $updated = $this->opportunityService->bulkAssign($opportunityIds, $assignee->id, $this->company->id);

        $this->assertEquals(3, $updated);
        
        foreach ($opportunities as $opportunity) {
            $opportunity->refresh();
            $this->assertEquals($assignee->id, $opportunity->assigned_to);
        }
    }

    public function test_can_get_forecast(): void
    {
        // Create opportunities with different probabilities and close dates
        $opp1 = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'status' => 'open',
            'amount' => 50000,
            'probability' => 80,
            'expected_close_date' => now()->addMonth(),
        ]);

        $opp2 = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'status' => 'open',
            'amount' => 30000,
            'probability' => 40,
            'expected_close_date' => now()->addMonth(),
        ]);

        $opp3 = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'status' => 'open',
            'amount' => 20000,
            'probability' => 20,
            'expected_close_date' => now()->addMonths(2),
        ]);

        $forecast = $this->opportunityService->getForecast($this->company->id);

        $this->assertEquals(3, $forecast['total_opportunities']);
        $this->assertEquals(100000, $forecast['total_value']);
        
        // Calculate expected weighted value: (50000*0.8) + (30000*0.4) + (20000*0.2) = 40000 + 12000 + 4000 = 56000
        $this->assertEquals(56000, $forecast['weighted_value']);
        $this->assertEquals(1, $forecast['by_probability']['high']['count']); // 80%
        $this->assertEquals(1, $forecast['by_probability']['medium']['count']); // 40%
        $this->assertEquals(1, $forecast['by_probability']['low']['count']); // 20%
    }
}