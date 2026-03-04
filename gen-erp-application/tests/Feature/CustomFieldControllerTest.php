<?php

namespace Tests\Feature;

use App\Domain\Shared\Models\CustomFieldDefinition;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\CompanyUser;
use App\Domain\Auth\Models\User;
use App\Services\CompanyContext;
use App\Support\Enums\CustomFieldType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomFieldControllerTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        
        CompanyUser::factory()->owner()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);
        
        CompanyContext::setActive($this->company);
        $this->actingAs($this->user);
    }

    public function test_can_view_custom_fields_index(): void
    {
        CustomFieldDefinition::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('documents.custom-fields.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Documents/CustomFields/Index')
                ->has('customFields.data', 3)
        );
    }

    public function test_can_create_custom_field(): void
    {
        $fieldData = [
            'domain' => 'sales',
            'entity_type' => 'customer',
            'field_key' => 'priority_level',
            'label' => 'Priority Level',
            'field_type' => CustomFieldType::SELECT->value,
            'help_text' => 'Select customer priority level',
            'is_required' => true,
            'is_filterable' => true,
            'options' => ['high', 'medium', 'low'],
            'security_level' => 'internal',
        ];

        $response = $this->post(route('documents.custom-fields.store'), $fieldData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'domain',
                'entity_type',
                'field_key',
                'label',
                'field_type',
            ],
        ]);

        $this->assertDatabaseHas('custom_field_definitions', [
            'domain' => 'sales',
            'entity_type' => 'customer',
            'field_key' => 'priority_level',
            'label' => 'Priority Level',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_can_show_custom_field(): void
    {
        $field = CustomFieldDefinition::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('documents.custom-fields.show', $field));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'domain',
                'entity_type',
                'field_key',
                'label',
                'creator',
            ],
        ]);
    }

    public function test_can_update_custom_field(): void
    {
        $field = CustomFieldDefinition::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'domain' => 'sales',
        ]);

        $updateData = [
            'label' => 'Updated Field Label',
            'help_text' => 'Updated help text',
            'is_required' => true,
            'is_filterable' => false,
        ];

        $response = $this->put(route('documents.custom-fields.update', $field), $updateData);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);

        $this->assertDatabaseHas('custom_field_definitions', [
            'id' => $field->id,
            'label' => 'Updated Field Label',
            'help_text' => 'Updated help text',
            'is_required' => true,
            'is_filterable' => false,
        ]);
    }

    public function test_can_delete_custom_field(): void
    {
        $field = CustomFieldDefinition::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->delete(route('documents.custom-fields.destroy', $field));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Custom field deleted successfully',
        ]);

        $this->assertDatabaseMissing('custom_field_definitions', [
            'id' => $field->id,
        ]);
    }

    public function test_can_get_custom_fields_grouped(): void
    {
        // Create fields in different domains
        CustomFieldDefinition::factory()->create([
            'company_id' => $this->company->id,
            'domain' => 'sales',
            'entity_type' => 'customer',
        ]);

        CustomFieldDefinition::factory()->create([
            'company_id' => $this->company->id,
            'domain' => 'inventory',
            'entity_type' => 'product',
        ]);

        $response = $this->get(route('documents.custom-fields.grouped'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'sales' => [
                    'customer' => [
                        '*' => [
                            'id',
                            'field_key',
                            'label',
                        ],
                    ],
                ],
                'inventory' => [
                    'product' => [
                        '*' => [
                            'id',
                            'field_key',
                            'label',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_can_get_custom_field_stats(): void
    {
        CustomFieldDefinition::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        CustomFieldDefinition::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'is_active' => false,
        ]);

        $response = $this->get(route('documents.custom-fields.stats'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_fields',
                'active_fields',
                'inactive_fields',
                'domains',
                'field_types',
            ],
        ]);

        $data = $response->json('data');
        $this->assertEquals(5, $data['total_fields']);
        $this->assertEquals(3, $data['active_fields']);
        $this->assertEquals(2, $data['inactive_fields']);
    }

    public function test_can_get_available_domains(): void
    {
        CustomFieldDefinition::factory()->create([
            'company_id' => $this->company->id,
            'domain' => 'sales',
        ]);

        CustomFieldDefinition::factory()->create([
            'company_id' => $this->company->id,
            'domain' => 'inventory',
        ]);

        $response = $this->get(route('documents.custom-fields.domains'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'value',
                    'label',
                    'count',
                ],
            ],
        ]);

        $domains = $response->json('data');
        $this->assertCount(2, $domains);
    }

    public function test_can_get_entity_types_for_domain(): void
    {
        CustomFieldDefinition::factory()->create([
            'company_id' => $this->company->id,
            'domain' => 'sales',
            'entity_type' => 'customer',
        ]);

        CustomFieldDefinition::factory()->create([
            'company_id' => $this->company->id,
            'domain' => 'sales',
            'entity_type' => 'order',
        ]);

        $response = $this->get(route('documents.custom-fields.entity-types', ['domain' => 'sales']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'value',
                    'label',
                    'count',
                ],
            ],
        ]);

        $entityTypes = $response->json('data');
        $this->assertCount(2, $entityTypes);
    }

    public function test_can_update_field_order(): void
    {
        $field1 = CustomFieldDefinition::factory()->create([
            'company_id' => $this->company->id,
            'display_order' => 0,
        ]);

        $field2 = CustomFieldDefinition::factory()->create([
            'company_id' => $this->company->id,
            'display_order' => 1,
        ]);

        $orderData = [
            'field_orders' => [
                $field1->id => 5,
                $field2->id => 3,
            ],
        ];

        $response = $this->post(route('documents.custom-fields.update-order'), $orderData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Field order updated successfully',
        ]);

        $this->assertEquals(5, $field1->fresh()->display_order);
        $this->assertEquals(3, $field2->fresh()->display_order);
    }

    public function test_cannot_access_other_company_custom_fields(): void
    {
        $otherCompany = Company::factory()->create();
        $otherField = CustomFieldDefinition::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->get(route('documents.custom-fields.show', $otherField));

        $response->assertStatus(404);
    }

    public function test_custom_field_validation_on_create(): void
    {
        $response = $this->post(route('documents.custom-fields.store'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'entity_type',
            'field_key',
            'label',
            'field_type',
        ]);
    }

    public function test_cannot_create_duplicate_field_key(): void
    {
        CustomFieldDefinition::factory()->create([
            'company_id' => $this->company->id,
            'entity_type' => 'customer',
            'field_key' => 'priority',
        ]);

        $fieldData = [
            'domain' => 'sales',
            'entity_type' => 'customer',
            'field_key' => 'priority', // Duplicate key
            'label' => 'Priority Level',
            'field_type' => CustomFieldType::TEXT->value,
        ];

        $response = $this->post(route('documents.custom-fields.store'), $fieldData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['field_key']);
    }

    public function test_can_filter_custom_fields(): void
    {
        CustomFieldDefinition::factory()->create([
            'company_id' => $this->company->id,
            'domain' => 'sales',
            'entity_type' => 'customer',
            'label' => 'Customer Priority',
        ]);

        CustomFieldDefinition::factory()->create([
            'company_id' => $this->company->id,
            'domain' => 'inventory',
            'entity_type' => 'product',
            'label' => 'Product Category',
        ]);

        // Test domain filter
        $response = $this->get(route('documents.custom-fields.index', ['domain' => 'sales']));
        $response->assertStatus(200);

        // Test search filter
        $response = $this->get(route('documents.custom-fields.index', ['search' => 'Priority']));
        $response->assertStatus(200);
    }
}