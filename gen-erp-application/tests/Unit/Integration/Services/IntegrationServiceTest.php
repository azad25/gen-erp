<?php

namespace Tests\Unit\Integration\Services;

use App\Domain\Integration\Models\Integration;
use App\Domain\Integration\Repositories\IntegrationRepository;
use App\Domain\Integration\Services\IntegrationService;
use App\Support\Enums\IntegrationCategory;
use App\Support\Enums\IntegrationTier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected IntegrationService $service;
    protected IntegrationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new IntegrationRepository();
        $this->service = new IntegrationService($this->repository);
    }

    public function test_can_get_available_integrations(): void
    {
        Integration::factory()->count(3)->create(['is_active' => true]);

        $integrations = $this->service->getAvailableIntegrations();

        $this->assertInstanceOf(Collection::class, $integrations);
        $this->assertCount(3, $integrations);
    }

    public function test_can_filter_integrations_by_category(): void
    {
        Integration::factory()->create([
            'category' => IntegrationCategory::ECOMMERCE,
            'is_active' => true,
        ]);
        Integration::factory()->create([
            'category' => IntegrationCategory::ACCOUNTING,
            'is_active' => true,
        ]);

        $integrations = $this->service->getAvailableIntegrations([
            'category' => 'ecommerce',
        ]);

        $this->assertCount(1, $integrations);
        $this->assertEquals(IntegrationCategory::ECOMMERCE, $integrations->first()->category);
    }

    public function test_can_find_integration_by_id(): void
    {
        $integration = Integration::factory()->create();

        $found = $this->service->findById($integration->id);

        $this->assertInstanceOf(Integration::class, $found);
        $this->assertEquals($integration->id, $found->id);
    }

    public function test_can_create_integration(): void
    {
        $data = [
            'slug' => 'test-integration',
            'name' => 'Test Integration',
            'category' => IntegrationCategory::ECOMMERCE,
            'tier' => IntegrationTier::NATIVE,
            'min_plan' => 'free',
            'is_active' => true,
        ];

        $integration = $this->service->create($data);

        $this->assertInstanceOf(Integration::class, $integration);
        $this->assertEquals('test-integration', $integration->slug);
        $this->assertEquals('Test Integration', $integration->name);
    }

    public function test_can_update_integration(): void
    {
        $integration = Integration::factory()->create([
            'name' => 'Original Name',
        ]);

        $updated = $this->service->update($integration->id, [
            'name' => 'Updated Name',
            'description' => 'New description',
        ]);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals('New description', $updated->description);
    }

    public function test_can_delete_integration(): void
    {
        $integration = Integration::factory()->create();

        $this->service->delete($integration->id);

        $this->assertDatabaseMissing('integrations', [
            'id' => $integration->id,
        ]);
    }

    public function test_can_check_plan_eligibility(): void
    {
        $integration = Integration::factory()->create([
            'min_plan' => 'pro',
        ]);

        $this->assertTrue($this->service->checkPlanEligibility($integration, 'enterprise'));
        $this->assertTrue($this->service->checkPlanEligibility($integration, 'pro'));
        $this->assertFalse($this->service->checkPlanEligibility($integration, 'free'));
    }
}
