<?php

namespace Tests\Feature\Domain\CRM;

use App\Domain\CRM\Models\Opportunity;
use App\Domain\CRM\Models\Pipeline;
use App\Domain\CRM\Models\PipelineStage;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PipelineApiTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

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
        
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_pipelines(): void
    {
        Pipeline::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/crm/pipelines');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'name',
                        'description',
                        'color',
                        'is_default',
                        'is_active',
                        'sort_order',
                        'stages',
                        'created_at',
                    ]
                ]
            ]);

        $this->assertEquals(3, count($response->json('data')));
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

        $response = $this->postJson('/api/v1/crm/pipelines', $pipelineData);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'name',
                    'description',
                    'color',
                    'is_default',
                    'is_active',
                    'sort_order',
                    'stages',
                ]
            ]);

        $this->assertDatabaseHas('pipelines', [
            'name' => 'Sales Pipeline',
            'description' => 'Main sales pipeline',
            'color' => '#3B82F6',
            'is_default' => true,
            'is_active' => true,
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        // Should have created default stages
        $pipeline = Pipeline::where('name', 'Sales Pipeline')->first();
        $this->assertGreaterThan(0, $pipeline->stages()->count());
    }

    public function test_create_pipeline_validation(): void
    {
        $response = $this->postJson('/api/v1/crm/pipelines', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_show_pipeline(): void
    {
        $pipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        // Create some stages
        PipelineStage::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $pipeline->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/crm/pipelines/{$pipeline->uuid}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'name',
                    'description',
                    'color',
                    'is_default',
                    'is_active',
                    'stages' => [
                        '*' => [
                            'id',
                            'uuid',
                            'name',
                            'probability',
                            'sort_order',
                        ]
                    ],
                    'created_by',
                ]
            ]);

        $this->assertEquals($pipeline->uuid, $response->json('data.uuid'));
        $this->assertEquals(3, count($response->json('data.stages')));
    }

    public function test_cannot_show_pipeline_from_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $pipeline = Pipeline::factory()->create([
            'company_id' => $otherCompany->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/crm/pipelines/{$pipeline->uuid}");

        $response->assertNotFound();
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

        $response = $this->putJson("/api/v1/crm/pipelines/{$pipeline->uuid}", $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'name',
                    'description',
                    'color',
                ]
            ]);

        $this->assertDatabaseHas('pipelines', [
            'id' => $pipeline->id,
            'name' => 'Updated Pipeline',
            'description' => 'Updated description',
            'color' => '#EF4444',
        ]);
    }

    public function test_can_delete_pipeline(): void
    {
        $pipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/v1/crm/pipelines/{$pipeline->uuid}");

        $response->assertOk()
            ->assertJson(['message' => __('crm.pipeline_deleted_successfully')]);

        $this->assertSoftDeleted('pipelines', ['id' => $pipeline->id]);
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

        $response = $this->deleteJson("/api/v1/crm/pipelines/{$pipeline->uuid}");

        $response->assertUnprocessable()
            ->assertJson(['message' => 'Cannot delete pipeline with existing opportunities']);
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

        $response = $this->postJson("/api/v1/crm/pipelines/{$pipeline2->uuid}/set-as-default");

        $response->assertOk()
            ->assertJson(['message' => __('crm.pipeline_set_as_default_successfully')]);

        $pipeline1->refresh();
        $pipeline2->refresh();

        $this->assertFalse($pipeline1->is_default);
        $this->assertTrue($pipeline2->is_default);
        $this->assertTrue($pipeline2->is_active);
    }

    public function test_can_activate_pipeline(): void
    {
        $pipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'is_active' => false,
        ]);

        $response = $this->postJson("/api/v1/crm/pipelines/{$pipeline->uuid}/activate");

        $response->assertOk()
            ->assertJson(['message' => __('crm.pipeline_activated_successfully')]);

        $pipeline->refresh();
        $this->assertTrue($pipeline->is_active);
    }

    public function test_can_deactivate_pipeline(): void
    {
        $pipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'is_active' => true,
            'is_default' => false,
        ]);

        $response = $this->postJson("/api/v1/crm/pipelines/{$pipeline->uuid}/deactivate");

        $response->assertOk()
            ->assertJson(['message' => __('crm.pipeline_deactivated_successfully')]);

        $pipeline->refresh();
        $this->assertFalse($pipeline->is_active);
    }

    public function test_cannot_deactivate_default_pipeline(): void
    {
        $pipeline = Pipeline::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'is_active' => true,
            'is_default' => true,
        ]);

        $response = $this->postJson("/api/v1/crm/pipelines/{$pipeline->uuid}/deactivate");

        $response->assertUnprocessable()
            ->assertJson(['message' => __('crm.cannot_deactivate_default_pipeline')]);
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

        $response = $this->postJson("/api/v1/crm/pipelines/{$pipeline->uuid}/stages", $stageData);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'name',
                    'description',
                    'color',
                    'probability',
                    'is_active',
                    'sort_order',
                ]
            ]);

        $this->assertDatabaseHas('pipeline_stages', [
            'name' => 'Qualification',
            'description' => 'Qualify the lead',
            'color' => '#10B981',
            'probability' => 25,
            'is_active' => true,
            'pipeline_id' => $pipeline->id,
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
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

        $response = $this->putJson("/api/v1/crm/pipelines/{$pipeline->uuid}/stages/{$stage->uuid}", $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'name',
                    'description',
                    'probability',
                ]
            ]);

        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage->id,
            'name' => 'Updated Stage',
            'description' => 'Updated description',
            'probability' => 75,
        ]);
    }

    public function test_can_delete_stage(): void
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

        $response = $this->deleteJson("/api/v1/crm/pipelines/{$pipeline->uuid}/stages/{$stage->uuid}");

        $response->assertOk()
            ->assertJson(['message' => __('crm.stage_deleted_successfully')]);

        $this->assertSoftDeleted('pipeline_stages', ['id' => $stage->id]);
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

        $response = $this->deleteJson("/api/v1/crm/pipelines/{$pipeline->uuid}/stages/{$stage->uuid}");

        $response->assertUnprocessable()
            ->assertJson(['message' => __('crm.cannot_delete_stage_with_opportunities')]);
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
        $response = $this->postJson("/api/v1/crm/pipelines/{$pipeline->uuid}/reorder-stages", [
            'stage_order' => [$stage3->id, $stage1->id, $stage2->id],
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.stages_reordered_successfully')]);

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

        // Create stages for original pipeline with sequential sort_order
        PipelineStage::factory()->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $originalPipeline->id,
            'created_by' => $this->user->id,
            'sort_order' => 1,
        ]);
        PipelineStage::factory()->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $originalPipeline->id,
            'created_by' => $this->user->id,
            'sort_order' => 2,
        ]);
        PipelineStage::factory()->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $originalPipeline->id,
            'created_by' => $this->user->id,
            'sort_order' => 3,
        ]);

        $response = $this->postJson("/api/v1/crm/pipelines/{$originalPipeline->uuid}/duplicate", [
            'name' => 'Duplicated Pipeline',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'name',
                    'description',
                    'is_default',
                    'is_active',
                    'stages',
                ]
            ]);

        $this->assertDatabaseHas('pipelines', [
            'name' => 'Duplicated Pipeline',
            'description' => 'Original description',
            'is_default' => false,
            'is_active' => true,
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        // Should have same number of stages
        $duplicatedPipeline = Pipeline::where('name', 'Duplicated Pipeline')->first();
        $this->assertEquals(
            $originalPipeline->stages()->count(),
            $duplicatedPipeline->stages()->count()
        );
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

        $response = $this->getJson("/api/v1/crm/pipelines/{$pipeline->uuid}/metrics");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_opportunities',
                    'total_value',
                    'won_opportunities',
                    'won_value',
                    'lost_opportunities',
                    'lost_value',
                    'open_opportunities',
                    'open_value',
                    'weighted_value',
                    'conversion_rate',
                    'average_deal_size',
                    'average_sales_cycle',
                    'stages',
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(5, $data['total_opportunities']);
        $this->assertEquals(300000, $data['total_value']); // (3*50000) + (2*75000)
        $this->assertEquals(2, $data['won_opportunities']);
        $this->assertEquals(150000, $data['won_value']); // 2*75000
        $this->assertEquals(3, $data['open_opportunities']);
        $this->assertEquals(150000, $data['open_value']); // 3*50000
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

        $response = $this->getJson('/api/v1/crm/pipelines/active');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'name',
                        'is_active',
                        'stages',
                    ]
                ]
            ]);

        $this->assertEquals(2, count($response->json('data')));
        foreach ($response->json('data') as $pipeline) {
            $this->assertTrue($pipeline['is_active']);
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

        $response = $this->getJson('/api/v1/crm/pipelines/default');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'name',
                    'is_default',
                    'stages',
                ]
            ]);

        $this->assertEquals($defaultPipeline->uuid, $response->json('data.uuid'));
        $this->assertTrue($response->json('data.is_default'));
    }
}