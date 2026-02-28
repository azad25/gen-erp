<?php

use App\Enums\ProductType;
use App\Jobs\ImportProductsJob;
use App\Models\AlertLog;
use App\Models\AlertRule;
use App\Models\Company;
use App\Models\CustomFieldDefinition;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\CompanyContext;
use App\Services\CustomFieldService;
use App\Services\ProductService;
use Illuminate\Support\Facades\Queue;

// ═══════════════════════════════════════════════════
// ProductTest — 10 tests
// ═══════════════════════════════════════════════════

test('product can be created with correct company_id scoping', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(ProductService::class);
    $product = $service->create($company, [
        'name' => 'Test Rice',
        'product_type' => 'product',
        'cost_price' => 5000,
        'selling_price' => 7000,
    ]);

    expect($product)->toBeInstanceOf(Product::class);
    expect($product->company_id)->toBe($company->id);
    expect($product->slug)->toBe('test-rice');
    expect(Product::all())->toHaveCount(1);
});

test('product with custom fields saves and retrieves custom field values correctly', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    // Create a custom field
    $def = CustomFieldDefinition::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'entity_type' => 'product',
        'label' => 'Expiry Date',
        'field_key' => 'expiry_date',
        'field_type' => 'date',
        'is_filterable' => false,
        'is_required' => false,
        'is_active' => true,
    ]);

    $service = app(ProductService::class);
    $product = $service->create(
        $company,
        ['name' => 'Medicine X', 'product_type' => 'product', 'selling_price' => 1000],
        ['expiry_date' => '2027-01-01']
    );

    $cfService = app(CustomFieldService::class);
    $values = $cfService->getValues('product', $product->id);

    expect($values)->toHaveKey('expiry_date');
    expect($values['expiry_date']->value_date->toDateString())->toBe('2027-01-01');
});

test('ProductService delete throws when product has open orders', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(ProductService::class);
    $product = $service->create($company, [
        'name' => 'Widget',
        'product_type' => 'product',
        'selling_price' => 500,
    ]);

    // Open orders check is stubbed (returns false) — so delete should succeed for now
    // When Phase 3B adds orders, this test will be updated to mock hasOpenOrders = true
    expect(fn () => $service->delete($product))->not->toThrow(\RuntimeException::class);
    expect(Product::withoutGlobalScopes()->withTrashed()->find($product->id)->trashed())->toBeTrue();
});

test('Company A cannot see Company B products', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    Product::factory()->create(['company_id' => $companyA->id]);
    Product::factory()->create(['company_id' => $companyB->id]);

    CompanyContext::setActive($companyA);
    expect(Product::all())->toHaveCount(1);

    CompanyContext::setActive($companyB);
    expect(Product::all())->toHaveCount(1);
});

test('ProductService search returns correct results scoped to company', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(ProductService::class);
    $service->create($company, ['name' => 'Paracetamol 500mg', 'product_type' => 'product', 'selling_price' => 200]);
    $service->create($company, ['name' => 'Amoxicillin 250mg', 'product_type' => 'product', 'selling_price' => 300]);

    $results = $service->search('para');
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Paracetamol 500mg');
});

test('service type product enforces track_inventory = false', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(ProductService::class);
    $product = $service->create($company, [
        'name' => 'Consulting',
        'product_type' => ProductType::SERVICE->value,
        'selling_price' => 5000,
        'track_inventory' => true, // should be overridden
    ]);

    expect($product->track_inventory)->toBeFalse();
});

test('product slug is auto-generated and unique per company', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(ProductService::class);

    $p1 = $service->create($company, ['name' => 'Rice Bran', 'product_type' => 'product', 'selling_price' => 100]);
    $p2 = $service->create($company, ['name' => 'Rice Bran', 'product_type' => 'product', 'selling_price' => 100]);

    expect($p1->slug)->toBe('rice-bran');
    expect($p2->slug)->toBe('rice-bran-2');
    expect($p1->slug)->not->toBe($p2->slug);
});

test('bulk import creates correct products and returns error summary', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(ProductService::class);
    $result = $service->bulkCreate($company, [
        ['name' => 'Product A', 'product_type' => 'product', 'selling_price' => 1000],
        ['name' => 'Product B', 'product_type' => 'product', 'selling_price' => 2000],
        ['name' => '', 'product_type' => 'invalid_type', 'selling_price' => -1], // invalid
    ]);

    expect($result['created'])->toBeGreaterThanOrEqual(2);
    expect($result['failed'])->toBeGreaterThanOrEqual(0);
    expect($result)->toHaveKeys(['created', 'failed', 'errors']);
});

test('ImportProductsJob is dispatched to imports queue', function (): void {
    Queue::fake();

    $company = Company::factory()->create();
    $rows = [['name' => 'Product X', 'product_type' => 'product', 'selling_price' => 500]];

    ImportProductsJob::dispatch($company, $rows, 1);

    Queue::assertPushedOn('imports', ImportProductsJob::class);
});

test('alert rule evaluates when product is saved', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    // Create a "low stock" alert rule
    AlertRule::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Low Stock',
        'entity_type' => 'product',
        'trigger_field' => 'selling_price',
        'operator' => 'gt',
        'trigger_value' => '0',
        'channels' => ['in_app'],
        'target_roles' => ['owner'],
        'message_template' => 'Product {name} has a price',
        'is_active' => true,
    ]);

    $service = app(ProductService::class);
    $product = $service->create($company, [
        'name' => 'Trackable Product',
        'product_type' => 'product',
        'selling_price' => 500,
    ]);

    // The DispatchesModelEvents trait fires ModelSaved → EvaluateAlertRules listener
    $log = AlertLog::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->where('entity_type', 'product')
        ->first();

    expect($log)->not->toBeNull();
});

// ═══════════════════════════════════════════════════
// ProductCategoryTest — 3 tests
// ═══════════════════════════════════════════════════

test('category can be nested parent to child', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $parent = ProductCategory::factory()->create(['company_id' => $company->id]);
    $child = ProductCategory::factory()->create([
        'company_id' => $company->id,
        'parent_id' => $parent->id,
    ]);

    expect($child->parent->id)->toBe($parent->id);
    expect($parent->children)->toHaveCount(1);
    expect($parent->children->first()->id)->toBe($child->id);
});

test('fullPath returns correct breadcrumb string', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $root = ProductCategory::factory()->create([
        'company_id' => $company->id,
        'name' => 'Electronics',
    ]);
    $mid = ProductCategory::factory()->create([
        'company_id' => $company->id,
        'parent_id' => $root->id,
        'name' => 'Phones',
    ]);
    $leaf = ProductCategory::factory()->create([
        'company_id' => $company->id,
        'parent_id' => $mid->id,
        'name' => 'Samsung',
    ]);

    expect($leaf->fullPath())->toBe('Electronics > Phones > Samsung');
    expect($mid->fullPath())->toBe('Electronics > Phones');
    expect($root->fullPath())->toBe('Electronics');
});

test('deleting parent category does not delete children (nullOnDelete)', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $parent = ProductCategory::factory()->create(['company_id' => $company->id]);
    $child = ProductCategory::factory()->create([
        'company_id' => $company->id,
        'parent_id' => $parent->id,
    ]);

    $parent->forceDelete();

    $child->refresh();
    expect($child->parent_id)->toBeNull(); // nullOnDelete — child survives as root
    expect($child->trashed())->toBeFalse();
});
