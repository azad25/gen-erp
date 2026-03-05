<?php

namespace Tests\Feature\Integration;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Integration\Models\Integration;
use App\Services\CompanyContext;
use App\Support\Enums\IntegrationCategory;
use App\Support\Enums\IntegrationTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

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

        CompanyContext::setActive($this->company);
        $this->actingAs($this->user);
    }

    public function test_can_list_available_integrations(): void
    {
        Integration::factory()->count(3)->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/integrations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'slug',
                        'name',
                        'category',
                        'category_label',
                        'description',
                        'tier',
                        'tier_label',
                        'is_active',
                        'is_official',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
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

        $response = $this->getJson('/api/v1/integrations?category=ecommerce');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'ecommerce');
    }

    public function test_can_search_integrations(): void
    {
        Integration::factory()->create([
            'name' => 'WooCommerce Integration',
            'is_active' => true,
        ]);
        Integration::factory()->create([
            'name' => 'Shopify Integration',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/integrations?search=WooCommerce');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'WooCommerce Integration');
    }

    public function test_can_get_single_integration(): void
    {
        $integration = Integration::factory()->create(['is_active' => true]);

        $response = $this->getJson("/api/v1/integrations/{$integration->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $integration->id)
            ->assertJsonPath('data.name', $integration->name);
    }

    public function test_can_create_integration(): void
    {
        $response = $this->postJson('/api/v1/integrations', [
            'slug' => 'test-integration',
            'name' => 'Test Integration',
            'category' => 'ecommerce',
            'description' => 'A test integration',
            'tier' => 'native',
            'min_plan' => 'free',
            'config_schema' => [],
            'capabilities' => [],
            'is_active' => true,
            'is_official' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', 'test-integration')
            ->assertJsonPath('data.name', 'Test Integration');

        $this->assertDatabaseHas('integrations', [
            'slug' => 'test-integration',
            'name' => 'Test Integration',
            'category' => 'ecommerce',
        ]);
    }

    public function test_cannot_create_integration_with_duplicate_slug(): void
    {
        Integration::factory()->create(['slug' => 'existing-integration']);

        $response = $this->postJson('/api/v1/integrations', [
            'slug' => 'existing-integration',
            'name' => 'Another Integration',
            'category' => 'ecommerce',
            'tier' => 'native',
            'min_plan' => 'free',
            'config_schema' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_can_update_integration(): void
    {
        $integration = Integration::factory()->create();

        $response = $this->putJson("/api/v1/integrations/{$integration->id}", [
            'name' => 'Updated Integration Name',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Integration Name');

        $this->assertDatabaseHas('integrations', [
            'id' => $integration->id,
            'name' => 'Updated Integration Name',
        ]);
    }

    public function test_can_delete_integration(): void
    {
        $integration = Integration::factory()->create();

        $response = $this->deleteJson("/api/v1/integrations/{$integration->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Integration deleted successfully.']);

        $this->assertDatabaseMissing('integrations', [
            'id' => $integration->id,
        ]);
    }

    public function test_only_active_integrations_are_listed(): void
    {
        Integration::factory()->create(['is_active' => true]);
        Integration::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/integrations');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_official_integrations_are_listed_first(): void
    {
        $unofficial = Integration::factory()->create([
            'name' => 'Unofficial Integration',
            'is_official' => false,
            'is_active' => true,
        ]);
        $official = Integration::factory()->create([
            'name' => 'Official Integration',
            'is_official' => true,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/integrations');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $official->id)
            ->assertJsonPath('data.1.id', $unofficial->id);
    }
}
