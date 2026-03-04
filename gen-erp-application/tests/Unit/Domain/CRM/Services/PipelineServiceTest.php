<?php

namespace Tests\Unit\Domain\CRM\Services;

use App\Domain\CRM\Contracts\PipelineServiceInterface;
use App\Domain\CRM\Models\Opportunity;
use App\Domain\CRM\Models\Pipeline;
use App\Domain\CRM\Models\PipelineStage;
use App\Domain\CRM\Services\PipelineService;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineServiceTest extends TestCase
{
    use RefreshDatabase;

    private PipelineServiceInterface $pipelineService;
    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->pipelineService = new PipelineService();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        
        // Associate user with company
        $this->user->companies()->attach($this->company->id, ['role' => 'admin']);
    }

    public function test_can_create_pipeline(): void
    {
        $pipelineData = [
            'name' => 'Sales Pipeline',
            'description' => 'Main sales pipeline',
            'color' => '#3B82F6',
            'is_default' => true,
            'is_active' => true,
            'create_default_stages' => true,
        ];

        $pipeline = $this->pipelineService->create($pipelineData, $this->company->id, $this->user->id);

        $this->assertInstanceOf(Pipeline::class, $pipeline);
        $this->assertEquals('Sales Pipeline', $pipeline->name);
        $this->assertEquals('Main sales pipeline', $pipeline->description);
        $this->assertEquals('#3B82F6', $pipeline->color);
        $this->assertTrue($pipeline->is_default);
        $this->assertTrue($pipeline->is_active);
        $this->assertEquals($this->company->id, $pipeline->company_id);
        $this->assertEquals($this->user->id, $pipeline->created_by);
        $this->assertEquals(1, $pipeline->sort_order);
        
        // Should have default stages
        $this->assertGreaterThan(0, $pipeline->stages()->count());
    }

    public function test_can_update_pipeline(): void
    {
        $pipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'name' => 'Original Name',
        ]);

        $updateData = [
            'name' => 'Updated Pipeline',
            'description' => 'Updated description',
            'color' => '#EF4444',
        ];

        $updatedPipeline = $this->pipelineService->update($pipeline, $updateData);

        $this->assertEquals('Updated Pipeline', $updatedPipeline->name);
        $this->assertEquals('Updated description', $updatedPipeline->description);
        $this->assertEquals('#EF4444', $updatedPipeline->color);
    }

    public function test_can_find_pipeline_by_uuid(): void
    {
        $pipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $foundPipeline = $this->pipelineService->findByUuid($pipeline->uuid, $this->company->id);

        $this->assertNotNull($foundPipeline);
        $this->assertEquals($pipeline->id, $foundPipeline->id);
        $this->assertEquals($pipeline->uuid, $foundPipeline->uuid);
    }

    public function test_cannot_find_pipeline_from_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $pipeline = Pipeline::factory()->create([
            'company_id' => $otherCompany->id,
            'created_by' => $this->user->id,
        ]);

        $foundPipeline = $this->pipelineService->findByUuid($pipeline->uuid, $this->company->id);

        $this->assertNull($foundPipeline);
    }

    public function test_can_set_as_default(): void
    {
        $pipeline1 = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'is_default' => true,
        ]);

        $pipeline2 = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'is_default' => false,
        ]);

        $defaultPipeline = $this->pipelineService->setAsDefault($pipeline2);

        $this->assertTrue($defaultPipeline->is_default);
        $this->assertTrue($defaultPipeline->is_active);
        
        // First pipeline should no longer be default
        $pipeline1->refresh();
        $this->assertFalse($pipeline1->is_default);
    }

    public function test_can_activate_pipeline(): void
    {
        $pipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'is_active' => false,
        ]);

        $activatedPipeline = $this->pipelineService->activate($pipeline);

        $this->assertTrue($activatedPipeline->is_active);
    }

    public function test_can_deactivate_pipeline(): void
    {
        $pipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'is_active' => true,
            'is_default' => false,
        ]);

        $deactivatedPipeline = $this->pipelineService->deactivate($pipeline);

        $this->assertFalse($deactivatedPipeline->is_active);
    }

    public function test_cannot_deactivate_default_pipeline(): void
    {
        $pipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot deactivate the default pipeline');

        $this->pipelineService->deactivate($pipeline);
    }

    public function test_can_create_stage(): void
    {
        $pipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $stageData = [
            'name' => 'Qualification',
            'description' => 'Qualify the lead',
            'color' => '#10B981',
            'probability' => 25,
            'is_active' => true,
        ];

        $stage = $this->pipelineService->createStage($pipeline, $stageData, $this->user->id);

        $this->assertInstanceOf(PipelineStage::class, $stage);
        $this->assertEquals('Qualification', $stage->name);
        $this->assertEquals('Qualify the lead', $stage->description);
        $this->assertEquals('#10B981', $stage->color);
        $this->assertEquals(25, $stage->probability);
        $this->assertTrue($stage->is_active);
        $this->assertEquals($pipeline->id, $stage->pipeline_id);
        $this->assertEquals($this->company->id, $stage->company_id);
        $this->assertEquals($this->user->id, $stage->created_by);
    }

    public function test_can_update_stage(): void
    {
        $pipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $stage = PipelineStage::factory()->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $pipeline->id,
            'created_by' => $this->user->id,
            'name' => 'Original Stage',
        ]);

        $updateData = [
            'name' => 'Updated Stage',
            'description' => 'Updated description',
            'probability' => 75,
        ];

        $updatedStage = $this->pipelineService->updateStage($stage, $updateData);

        $this->assertEquals('Updated Stage', $updatedStage->name);
        $this->assertEquals('Updated description', $updatedStage->description);
        $this->assertEquals(75, $updatedStage->probability);
    }

    public function test_can_reorder_stages(): void
    {
        $pipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $stage1 = PipelineStage::factory()->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $pipeline->id,
            'created_by' => $this->user->id,
            'sort_order' => 1,
        ]);

        $stage2 = PipelineStage::factory()->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $pipeline->id,
            'created_by' => $this->user->id,
            'sort_order' => 2,
        ]);

        $stage3 = PipelineStage::factory()->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $pipeline->id,
            'created_by' => $this->user->id,
            'sort_order' => 3,
        ]);

        // Reorder: stage3, stage1, stage2
        $newOrder = [$stage3->id, $stage1->id, $stage2->id];
        $this->pipelineService->reorderStages($pipeline, $newOrder);

        $stage1->refresh();
        $stage2->refresh();
        $stage3->refresh();

        $this->assertEquals(2, $stage1->sort_order);
        $this->assertEquals(3, $stage2->sort_order);
        $this->assertEquals(1, $stage3->sort_order);
    }

    public function test_can_duplicate_pipeline(): void
    {
        $originalPipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'name' => 'Original Pipeline',
            'description' => 'Original description',
        ]);

        // Create stages for original pipeline with unique sort orders
        PipelineStage::factory()->count(3)->sequence(
            ['sort_order' => 100],
            ['sort_order' => 101],
            ['sort_order' => 102]
        )->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $originalPipeline->id,
            'created_by' => $this->user->id,
        ]);

        $duplicatedPipeline = $this->pipelineService->duplicatePipeline(
            $originalPipeline,
            'Duplicated Pipeline',
            $this->user->id
        );

        $this->assertInstanceOf(Pipeline::class, $duplicatedPipeline);
        $this->assertEquals('Duplicated Pipeline', $duplicatedPipeline->name);
        $this->assertEquals('Original description', $duplicatedPipeline->description);
        $this->assertFalse($duplicatedPipeline->is_default);
        $this->assertTrue($duplicatedPipeline->is_active);
        $this->assertEquals($this->company->id, $duplicatedPipeline->company_id);
        $this->assertEquals($this->user->id, $duplicatedPipeline->created_by);
        
        // Should have same number of stages
        $this->assertEquals(
            $originalPipeline->stages()->count(),
            $duplicatedPipeline->stages()->count()
        );
    }

    public function test_can_get_active_pipelines(): void
    {
        Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'is_active' => true,
        ]);

        Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'is_active' => false,
        ]);

        Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'is_active' => true,
        ]);

        $activePipelines = $this->pipelineService->getActive($this->company->id);

        $this->assertEquals(2, $activePipelines->count());
        foreach ($activePipelines as $pipeline) {
            $this->assertTrue($pipeline->is_active);
        }
    }

    public function test_can_get_default_pipeline(): void
    {
        Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'is_default' => false,
        ]);

        $defaultPipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'is_default' => true,
        ]);

        $foundDefault = $this->pipelineService->getDefault($this->company->id);

        $this->assertNotNull($foundDefault);
        $this->assertEquals($defaultPipeline->id, $foundDefault->id);
        $this->assertTrue($foundDefault->is_default);
    }

    public function test_cannot_delete_pipeline_with_opportunities(): void
    {
        $pipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $stage = PipelineStage::factory()->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $pipeline->id,
            'created_by' => $this->user->id,
        ]);

        // Create opportunity in pipeline
        Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete pipeline with existing opportunities');

        $this->pipelineService->delete($pipeline);
    }

    public function test_cannot_delete_stage_with_opportunities(): void
    {
        $pipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $stage = PipelineStage::factory()->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $pipeline->id,
            'created_by' => $this->user->id,
        ]);

        // Create opportunity in stage
        Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete stage with existing opportunities');

        $this->pipelineService->deleteStage($stage);
    }

    public function test_can_get_pipeline_metrics(): void
    {
        $pipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $stage = PipelineStage::factory()->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $pipeline->id,
            'created_by' => $this->user->id,
        ]);

        // Create opportunities with different statuses
        Opportunity::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'status' => 'open',
            'amount' => 50000,
        ]);

        Opportunity::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'status' => 'won',
            'amount' => 75000,
        ]);

        Opportunity::factory()->count(1)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'status' => 'lost',
            'amount' => 25000,
        ]);

        $metrics = $this->pipelineService->getPipelineMetrics($pipeline);

        $this->assertEquals(6, $metrics['total_opportunities']);
        // Calculate expected total: (3*50000) + (2*75000) + (1*25000) = 150000 + 150000 + 25000 = 325000
        $this->assertEquals(325000, $metrics['total_value']);
        $this->assertEquals(2, $metrics['won_opportunities']);
        $this->assertEquals(150000, $metrics['won_value']); // 2*75000
        $this->assertEquals(1, $metrics['lost_opportunities']);
        $this->assertEquals(25000, $metrics['lost_value']); // 1*25000
        $this->assertEquals(3, $metrics['open_opportunities']);
        $this->assertEquals(150000, $metrics['open_value']); // 3*50000
        $this->assertEquals(33.33, $metrics['conversion_rate']); // 2/6 * 100
        $this->assertEquals(54166.67, $metrics['average_deal_size']); // 325000/6
        $this->assertIsArray($metrics['stages']);
    }
}