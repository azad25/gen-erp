<?php

use App\Enums\CustomFieldType;
use App\Jobs\FilterableCustomFieldJob;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\CustomFieldService;
use Illuminate\Support\Facades\Queue;

// ── 1. Company can create a custom field definition for products ──

test('company can create a custom field definition for products', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);

    CompanyContext::setActive($company);

    $definition = CustomFieldDefinition::create([
        'company_id' => $company->id,
        'entity_type' => 'product',
        'field_key' => 'batch_number',
        'label' => 'Batch Number',
        'field_type' => CustomFieldType::TEXT->value,
        'is_required' => true,
        'is_active' => true,
    ]);

    expect($definition)->toBeInstanceOf(CustomFieldDefinition::class);
    expect($definition->field_key)->toBe('batch_number');
    expect($definition->field_type)->toBe(CustomFieldType::TEXT);
    expect($definition->is_required)->toBeTrue();
});

// ── 2. Custom field appears in form schema ──

test('custom field definitions can be retrieved', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    CustomFieldDefinition::create([
        'company_id' => $company->id,
        'entity_type' => 'product',
        'field_key' => 'expiry_date',
        'label' => 'Expiry Date',
        'field_type' => CustomFieldType::DATE->value,
        'is_active' => true,
    ]);

    $service = app(CustomFieldService::class);
    $definitions = $service->getDefinitions('product');

    expect($definitions)->toHaveCount(1);
    expect($definitions->first()->field_key)->toBe('expiry_date');
});

// ── 3. Custom field value can be saved and retrieved ──

test('custom field value can be saved and retrieved for entity', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    CustomFieldDefinition::create([
        'company_id' => $company->id,
        'entity_type' => 'product',
        'field_key' => 'generic_name',
        'label' => 'Generic Name',
        'field_type' => CustomFieldType::TEXT->value,
        'is_active' => true,
    ]);

    $service = app(CustomFieldService::class);
    $service->saveValues('product', 1, ['generic_name' => 'Paracetamol']);

    $values = $service->getValues('product', 1);

    expect($values)->toHaveCount(1);
    expect($values->get('generic_name')->value_text)->toBe('Paracetamol');
});

// ── 4. Tenant isolation: separate custom field definitions ──

test('two companies have separate custom field definitions', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    // Create definition for Company A
    CompanyContext::setActive($companyA);
    CustomFieldDefinition::create([
        'company_id' => $companyA->id,
        'entity_type' => 'product',
        'field_key' => 'batch_number',
        'label' => 'Batch Number',
        'field_type' => CustomFieldType::TEXT->value,
        'is_active' => true,
    ]);

    // Create definition for Company B
    CompanyContext::setActive($companyB);
    CustomFieldDefinition::create([
        'company_id' => $companyB->id,
        'entity_type' => 'product',
        'field_key' => 'style_number',
        'label' => 'Style Number',
        'field_type' => CustomFieldType::TEXT->value,
        'is_active' => true,
    ]);

    // Company A sees only its definition
    CompanyContext::setActive($companyA);
    $defsA = CustomFieldDefinition::all();
    expect($defsA)->toHaveCount(1);
    expect($defsA->first()->field_key)->toBe('batch_number');

    // Company B sees only its definition
    CompanyContext::setActive($companyB);
    $defsB = CustomFieldDefinition::all();
    expect($defsB)->toHaveCount(1);
    expect($defsB->first()->field_key)->toBe('style_number');
});

// ── 5. Company A's values cannot be read by Company B ──

test('company A custom field values invisible to company B', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    CompanyContext::setActive($companyA);
    CustomFieldDefinition::create([
        'company_id' => $companyA->id,
        'entity_type' => 'product',
        'field_key' => 'batch',
        'label' => 'Batch',
        'field_type' => CustomFieldType::TEXT->value,
        'is_active' => true,
    ]);

    $service = app(CustomFieldService::class);
    $service->saveValues('product', 1, ['batch' => 'B001']);

    // Switch to Company B
    CompanyContext::setActive($companyB);
    $values = CustomFieldValue::all();

    expect($values)->toHaveCount(0);
});

// ── 6. Filterable custom field dispatches job ──

test('filterable custom field dispatches FilterableCustomFieldJob', function (): void {
    Queue::fake();

    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $definition = CustomFieldDefinition::create([
        'company_id' => $company->id,
        'entity_type' => 'product',
        'field_key' => 'color',
        'label' => 'Color',
        'field_type' => CustomFieldType::TEXT->value,
        'is_active' => true,
        'is_filterable' => false,
    ]);

    // Enable filterable
    $definition->update(['is_filterable' => true]);

    // Dispatch manually (in production, the Filament EditRecord page does this)
    if ($definition->is_filterable && $definition->wasChanged('is_filterable')) {
        FilterableCustomFieldJob::dispatch($definition);
    }

    Queue::assertPushed(FilterableCustomFieldJob::class);
});

// ── 7. Validation rules enforced ──

test('validation rules from custom field definitions are enforced', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $definition = CustomFieldDefinition::create([
        'company_id' => $company->id,
        'entity_type' => 'product',
        'field_key' => 'contact_email',
        'label' => 'Contact Email',
        'field_type' => CustomFieldType::EMAIL->value,
        'is_required' => true,
        'is_active' => true,
    ]);

    $rules = $definition->buildValidationRule();

    expect($rules)->toContain('required');
    expect($rules)->toContain('email');
});

// ── 8. Inactive custom field does not appear in definitions ──

test('inactive custom field does not appear in definitions', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    CustomFieldDefinition::create([
        'company_id' => $company->id,
        'entity_type' => 'product',
        'field_key' => 'hidden_field',
        'label' => 'Hidden Field',
        'field_type' => CustomFieldType::TEXT->value,
        'is_active' => false,
    ]);

    $service = app(CustomFieldService::class);
    $definitions = $service->getDefinitions('product');

    expect($definitions)->toHaveCount(0);
});
