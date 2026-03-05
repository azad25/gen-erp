<?php

namespace Tests\Unit\Integration\Services;

use App\Domain\Auth\Models\Company;
use App\Domain\Integration\Events\IntegrationInstalled;
use App\Domain\Integration\Events\IntegrationUninstalled;
use App\Domain\Integration\Models\CompanyIntegration;
use App\Domain\Integration\Models\Integration;
use App\Domain\Integration\Repositories\CompanyIntegrationRepository;
use App\Domain\Integration\Repositories\IntegrationRepository;
use App\Domain\Integration\Services\CompanyIntegrationService;
use App\Domain\Integration\Services\SyncEngine;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CompanyIntegrationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CompanyIntegrationService $service;
    protected CompanyIntegrationRepository $companyIntegrationRepository;
    protected IntegrationRepository $integrationRepository;
    protected SyncEngine $syncEngine;
    protected Company $company;
    protected Integration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->integration = Integration::factory()->create(['is_active' => true]);

        $this->companyIntegrationRepository = new CompanyIntegrationRepository();
        $this->integrationRepository = new IntegrationRepository();
        $this->syncEngine = new SyncEngine();

        $this->service = new CompanyIntegrationService(
            $this->companyIntegrationRepository,
            $this->integrationRepository,
            $this->syncEngine
        );
    }

    public function test_can_get_company_integrations(): void
    {
        CompanyIntegration::factory()->count(2)->create([
            'company_id' => $this->company->id,
        ]);

        $integrations = $this->service->getCompanyIntegrations($this->company->id);

        $this->assertInstanceOf(Collection::class, $integrations);
        $this->assertCount(2, $integrations);
    }

    public function test_can_install_integration(): void
    {
        Event::fake();

        $companyIntegration = $this->service->install(
            companyId: $this->company->id,
            integrationId: $this->integration->id,
            config: ['api_key' => 'test-key']
        );

        $this->assertInstanceOf(CompanyIntegration::class, $companyIntegration);
        $this->assertEquals($this->company->id, $companyIntegration->company_id);
        $this->assertEquals($this->integration->id, $companyIntegration->integration_id);
        $this->assertEquals('active', $companyIntegration->status);
        $this->assertEquals(['api_key' => 'test-key'], $companyIntegration->config);

        Event::assertDispatched(IntegrationInstalled::class);
    }

    public function test_cannot_install_same_integration_twice(): void
    {
        CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
            'integration_id' => $this->integration->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Integration already installed.');

        $this->service->install(
            companyId: $this->company->id,
            integrationId: $this->integration->id,
            config: []
        );
    }

    public function test_can_find_company_integration_by_id(): void
    {
        $companyIntegration = CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $found = $this->service->findById($companyIntegration->id, $this->company->id);

        $this->assertInstanceOf(CompanyIntegration::class, $found);
        $this->assertEquals($companyIntegration->id, $found->id);
    }

    public function test_can_update_integration_config(): void
    {
        $companyIntegration = CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
            'config' => ['api_key' => 'old-key'],
        ]);

        $updated = $this->service->updateConfig(
            id: $companyIntegration->id,
            companyId: $this->company->id,
            config: ['api_key' => 'new-key'],
            fieldMaps: ['customer_name' => 'full_name']
        );

        $this->assertEquals(['api_key' => 'new-key'], $updated->config);
        $this->assertEquals(['customer_name' => 'full_name'], $updated->field_maps);
    }

    public function test_can_activate_integration(): void
    {
        $companyIntegration = CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'paused',
        ]);

        $activated = $this->service->activate($companyIntegration->id, $this->company->id);

        $this->assertEquals('active', $activated->status);
    }

    public function test_can_deactivate_integration(): void
    {
        $companyIntegration = CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);

        $deactivated = $this->service->deactivate($companyIntegration->id, $this->company->id);

        $this->assertEquals('paused', $deactivated->status);
    }

    public function test_can_uninstall_integration(): void
    {
        Event::fake();

        $companyIntegration = CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->service->uninstall($companyIntegration->id, $this->company->id);

        $this->assertDatabaseMissing('company_integrations', [
            'id' => $companyIntegration->id,
        ]);

        Event::assertDispatched(IntegrationUninstalled::class);
    }

    public function test_cannot_sync_inactive_integration(): void
    {
        $companyIntegration = CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'paused',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot sync inactive integration.');

        $this->service->triggerSync($companyIntegration->id, $this->company->id);
    }

    public function test_can_filter_by_status(): void
    {
        CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);
        CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'paused',
        ]);

        $activeIntegrations = $this->service->getCompanyIntegrations(
            $this->company->id,
            ['status' => 'active']
        );

        $this->assertCount(1, $activeIntegrations);
        $this->assertEquals('active', $activeIntegrations->first()->status);
    }
}
