<?php

namespace Tests\Feature\Integration;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Integration\Events\IntegrationInstalled;
use App\Domain\Integration\Events\IntegrationUninstalled;
use App\Domain\Integration\Models\CompanyIntegration;
use App\Domain\Integration\Models\Integration;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CompanyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Integration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        
        $this->company->users()->attach($this->user->id, [
            'role' => 'admin',
            'is_owner' => true,
            'is_active' => true,
        ]);

        $this->integration = Integration::factory()->create(['is_active' => true]);

        CompanyContext::setActive($this->company);
        $this->actingAs($this->user);
    }

    public function test_can_list_company_integrations(): void
    {
        CompanyIntegration::factory()->count(2)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->getJson('/api/v1/integrations/company');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'company_id',
                        'integration',
                        'config',
                        'status',
                        'last_sync_at',
                        'installed_at',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_can_install_integration(): void
    {
        Event::fake();

        $response = $this->postJson('/api/v1/integrations/company', [
            'integration_id' => $this->integration->id,
            'config' => [
                'api_key' => 'test-key',
                'endpoint' => 'https://api.example.com',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.company_id', $this->company->id)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.config.api_key', 'test-key');

        $this->assertDatabaseHas('company_integrations', [
            'company_id' => $this->company->id,
            'integration_id' => $this->integration->id,
            'status' => 'active',
        ]);

        Event::assertDispatched(IntegrationInstalled::class);
    }

    public function test_cannot_install_same_integration_twice(): void
    {
        CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
            'integration_id' => $this->integration->id,
        ]);

        $response = $this->postJson('/api/v1/integrations/company', [
            'integration_id' => $this->integration->id,
            'config' => [],
        ]);

        $response->assertStatus(500);
    }

    public function test_can_get_single_company_integration(): void
    {
        $companyIntegration = CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->getJson("/api/v1/integrations/company/{$companyIntegration->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $companyIntegration->id)
            ->assertJsonPath('data.company_id', $this->company->id);
    }

    public function test_cannot_access_other_company_integration(): void
    {
        $otherCompany = Company::factory()->create();
        $companyIntegration = CompanyIntegration::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->getJson("/api/v1/integrations/company/{$companyIntegration->id}");

        $response->assertStatus(404);
    }

    public function test_can_update_integration_config(): void
    {
        $companyIntegration = CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
            'config' => ['api_key' => 'old-key'],
        ]);

        $response = $this->putJson("/api/v1/integrations/company/{$companyIntegration->id}", [
            'config' => [
                'api_key' => 'new-key',
                'endpoint' => 'https://new-api.example.com',
            ],
            'field_maps' => [
                'customer_name' => 'full_name',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.config.api_key', 'new-key')
            ->assertJsonPath('data.field_maps.customer_name', 'full_name');

        $this->assertDatabaseHas('company_integrations', [
            'id' => $companyIntegration->id,
        ]);
    }

    public function test_can_activate_integration(): void
    {
        $companyIntegration = CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'paused',
        ]);

        $response = $this->postJson("/api/v1/integrations/company/{$companyIntegration->id}/activate");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Integration activated successfully.']);

        $this->assertDatabaseHas('company_integrations', [
            'id' => $companyIntegration->id,
            'status' => 'active',
        ]);
    }

    public function test_can_deactivate_integration(): void
    {
        $companyIntegration = CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/v1/integrations/company/{$companyIntegration->id}/deactivate");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Integration deactivated successfully.']);

        $this->assertDatabaseHas('company_integrations', [
            'id' => $companyIntegration->id,
            'status' => 'paused',
        ]);
    }

    public function test_can_trigger_sync(): void
    {
        $companyIntegration = CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/v1/integrations/company/{$companyIntegration->id}/sync");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Sync triggered successfully.']);
    }

    public function test_cannot_sync_inactive_integration(): void
    {
        $companyIntegration = CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'paused',
        ]);

        $response = $this->postJson("/api/v1/integrations/company/{$companyIntegration->id}/sync");

        $response->assertStatus(500);
    }

    public function test_can_uninstall_integration(): void
    {
        Event::fake();

        $companyIntegration = CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->deleteJson("/api/v1/integrations/company/{$companyIntegration->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Integration uninstalled successfully.']);

        $this->assertDatabaseMissing('company_integrations', [
            'id' => $companyIntegration->id,
        ]);

        Event::assertDispatched(IntegrationUninstalled::class);
    }

    public function test_can_filter_integrations_by_status(): void
    {
        CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);
        CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'paused',
        ]);

        $response = $this->getJson('/api/v1/integrations/company?status=active');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'active');
    }

    public function test_can_search_company_integrations(): void
    {
        $integration1 = Integration::factory()->create([
            'name' => 'WooCommerce',
            'is_active' => true,
        ]);
        $integration2 = Integration::factory()->create([
            'name' => 'Shopify',
            'is_active' => true,
        ]);

        CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
            'integration_id' => $integration1->id,
        ]);
        CompanyIntegration::factory()->create([
            'company_id' => $this->company->id,
            'integration_id' => $integration2->id,
        ]);

        $response = $this->getJson('/api/v1/integrations/company?search=WooCommerce');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
