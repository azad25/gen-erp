<?php

namespace Tests\Unit;

use App\Domain\Document\Services\CustomFieldManagementService;
use App\Domain\Shared\Models\CustomFieldDefinition;
use App\Domain\Shared\Services\CustomFieldService;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Services\CompanyContext;
use App\Support\Enums\CustomFieldType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomFieldManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomFieldManagementService $service;
    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(CustomFieldManagementService::class);
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        
        CompanyContext::setActive($this->company);
        $this->actingAs($this->user);
    }

    public function test_can_create_custom_field(): void
    {
        $fieldData = [
            'domain' => 'sales',
            'entity_type' => 'customer',
            'field_key' => 'priority_level',
            'label' => 'Priority Level',
            'field_type' => CustomFieldType::SELECT->value,
            'options' => ['high', 'medium', 'low'],
            'is_required' => true,
            'security_level' => 'internal',
        ];

        $field = $this->service->createCustomField($fieldData);

        $this->assertInstanceOf(CustomFieldDefinition::class, $field);
        $this->assertEquals('sales', $field->domain);
        $this->assertEquals('customer', $field->entity_type);
        $this->assertEquals('priority_level', $field->field_key);
        $this->assertEquals('Priority Level', $field->label);
        $this->assertEquals(CustomFieldType::SELECT, $field->field_type);
        $this->assertTrue($field->is_required);
        $this->assertEquals($this->company->id, $field->company_id);
        $this->assertEquals($this->user->id, $field->created_by);
    }

    public function test_can_update_custom_field(): void
    {
        $field = CustomFieldDefinition::factory()->create([
            'company_id' => $this->company->id,
            'domain' => 'sales',
            'entity_type' => 'customer',
            'field_key' => 'test_field',
            'label' => 'Test Field',
        ]);

        $updateData = [
            'label' => 'Updated Test Field',
            'help_text' => 'This is help text',
            'is_required' => true,
        ];

        $updatedField = $this->service->updateCustomField($field, $updateData);

        $this->assertEquals('Updated Test Field', $updatedField->label);
        $this->assertEquals('This is help text', $updatedField->help_text);
        $this->assertTrue($updatedField->is_required);
    }

    public function test_can_delete_custom_field(): void
    {
        $field = CustomFieldDefinition::factory()->create([
            'company_id' => $this->company->id,
            'domain' => 'sales',
        ]);

        $result = $this->service->deleteCustomField($field);

        $this->assertTrue($result);
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
            'domain' => 'sales',
            'entity_type' => 'order',
        ]);

        CustomFieldDefinition::factory()->create([
            'company_id' => $this->company->id,
            'domain' => 'inventory',
            'entity_type' => 'product',
        ]);

        $grouped = $this->service->getAllCustomFieldsGrouped();

        $this->assertArrayHasKey('sales', $grouped);
        $this->assertArrayHasKey('inventory', $grouped);
        $this->assertArrayHasKey('customer', $grouped['sales']);
        $this->assertArrayHasKey('order', $grouped['sales']);
        $this->assertArrayHasKey('product', $grouped['inventory']);
    }

    public function test_can_get_paginated_custom_fields(): void
    {
        CustomFieldDefinition::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'domain' => 'sales',
        ]);

        $result = $this->service->getCustomFields([], 3);

        $this->assertEquals(3, $result->perPage());
        $this->assertEquals(5, $result->total());
        $this->assertCount(3, $result->items());
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
        $result = $this->service->getCustomFields(['domain' => 'sales']);
        $this->assertEquals(1, $result->total());

        // Test search filter
        $result = $this->service->getCustomFields(['search' => 'Priority']);
        $this->assertEquals(1, $result->total());
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

        $domains = $this->service->getAvailableDomains();

        $this->assertCount(2, $domains);
        
        $domainValues = $domains->pluck('value')->toArray();
        $this->assertContains('sales', $domainValues);
        $this->assertContains('inventory', $domainValues);
        
        $salesDomain = $domains->firstWhere('value', 'sales');
        $this->assertEquals('Sales', $salesDomain['label']);
        $this->assertEquals(1, $salesDomain['count']);
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

        $entityTypes = $this->service->getEntityTypesForDomain('sales');

        $this->assertCount(2, $entityTypes);
        $this->assertEquals('customer', $entityTypes->first()['value']);
        $this->assertEquals('Customer', $entityTypes->first()['label']);
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

        $stats = $this->service->getCustomFieldStats();

        $this->assertEquals(5, $stats['total_fields']);
        $this->assertEquals(3, $stats['active_fields']);
        $this->assertEquals(2, $stats['inactive_fields']);
        $this->assertArrayHasKey('domains', $stats);
        $this->assertArrayHasKey('field_types', $stats);
    }

    public function test_validates_domain_access(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid domain: invalid_domain');

        $this->service->createCustomField([
            'domain' => 'invalid_domain',
            'entity_type' => 'test',
            'field_key' => 'test',
            'label' => 'Test',
            'field_type' => CustomFieldType::TEXT->value,
        ]);
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

        $fieldOrders = [
            $field1->id => 5,
            $field2->id => 3,
        ];

        $result = $this->service->updateFieldOrder($fieldOrders);

        $this->assertTrue($result);
        $this->assertEquals(5, $field1->fresh()->display_order);
        $this->assertEquals(3, $field2->fresh()->display_order);
    }
}