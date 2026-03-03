<?php

use App\Support\Enums\ProductType as ProductTypeEnum;
use App\Domain\Auth\Models\Company;
use App\Domain\Shared\Models\CustomFieldDefinition;
use App\Domain\Product\Models\Product;
use App\Domain\Product\DTOs\CreateProductData;
use App\Domain\Product\Models\ProductCategory;
use App\Services\CompanyContext;
use App\Domain\Shared\Services\CustomFieldService;
use App\Domain\Product\Services\ProductService;
use App\Jobs\ImportProductsJob as ImportJob;
use App\Domain\Shared\Models\AlertRule as SharedAlertRule;
use App\Domain\Shared\Models\AlertLog as SharedAlertLog;
use Illuminate\Support\Facades\Queue;

// ═══════════════════════════════════════════════════
// ProductTest — 10 tests
// ═══════════════════════════════════════════════════

test('product can be created with correct company_id scoping', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(ProductService::class);
    $data = CreateProductData::from([
        'company_id' => $company->id,
        'name' => 'Test Rice',
        'product_type' => ProductTypeEnum::PRODUCT,
        'cost_price' => 5000,
        'selling_price' => 7000,
    ]);
    $product = $service->create($data);

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
    $data = CreateProductData::from([
        'company_id' => $company->id,
        'name' => 'Medicine X',
        'product_type' => ProductTypeEnum::PRODUCT,
        'selling_price' => 1000,
        'custom_fields' => ['expiry_date' => '2027-01-01']
    ]);
    $product = $service->create($data);

    $cfService = app(CustomFieldService::class);
    $values = $cfService->getValues('product', $product->id);

    expect($values)->toHaveKey('expiry_date');
    expect($values['expiry_date']->value_date->toDateString())->toBe('2027-01-01');
});

test('ProductService delete throws when product has open orders', function (): void {
    $company = Company::factory()->create();
    $warehouse = \App\Domain\Inventory\Models\Warehouse::factory()->create(['company_id' => $company->id]);
    CompanyContext::setActive($company);

    $service = app(ProductService::class);
    $data = CreateProductData::from([
        'company_id' => $company->id,
        'name' => 'Widget',
        'product_type' => ProductTypeEnum::PRODUCT,
        'selling_price' => 500,
    ]);
    $product = $service->create($data);

    // Create an open sales order with this product
    $salesOrder = \App\Domain\SalesOrder\Models\SalesOrder::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'customer_id' => \App\Domain\Customer\Models\Customer::factory()->create(['company_id' => $company->id])->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'draft',
        'order_date' => now(),
        'subtotal' => 500,
        'total_amount' => 500,
    ]);

    \App\Domain\SalesOrder\Models\SalesOrderItem::withoutGlobalScopes()->create([
        'sales_order_id' => $salesOrder->id,
        'product_id' => $product->id,
        'description' => 'Test product',
        'quantity' => 1,
        'unit' => 'pcs',
        'unit_price' => 500,
        'line_total' => 500,
    ]);

    // Should throw exception when trying to delete
    expect(fn () => $service->delete($product))->toThrow(\RuntimeException::class);
});

test('ProductService can delete product when no open orders', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(ProductService::class);
    $data = CreateProductData::from([
        'company_id' => $company->id,
        'name' => 'Widget',
        'product_type' => ProductTypeEnum::PRODUCT,
        'selling_price' => 500,
    ]);
    $product = $service->create($data);

    // Product can be deleted when there are no open orders
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
    $data1 = CreateProductData::from([
        'company_id' => $company->id,
        'name' => 'Paracetamol 500mg',
        'product_type' => ProductTypeEnum::PRODUCT,
        'selling_price' => 200
    ]);
    $data2 = CreateProductData::from([
        'company_id' => $company->id,
        'name' => 'Amoxicillin 250mg',
        'product_type' => ProductTypeEnum::PRODUCT,
        'selling_price' => 300
    ]);
    $service->create($data1);
    $service->create($data2);

    $results = $service->search('para');
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Paracetamol 500mg');
});

test('service type product enforces track_inventory = false', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(ProductService::class);
    $data = CreateProductData::from([
        'company_id' => $company->id,
        'name' => 'Consulting',
        'product_type' => ProductTypeEnum::SERVICE,
        'selling_price' => 5000,
        'track_inventory' => true, // should be overridden
    ]);
    $product = $service->create($data);

    expect($product->track_inventory)->toBeFalse();
});

test('product slug is auto-generated and unique per company', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(ProductService::class);

    $data1 = CreateProductData::from([
        'company_id' => $company->id,
        'name' => 'Rice Bran',
        'product_type' => ProductTypeEnum::PRODUCT,
        'selling_price' => 100
    ]);
    $data2 = CreateProductData::from([
        'company_id' => $company->id,
        'name' => 'Rice Bran',
        'product_type' => ProductTypeEnum::PRODUCT,
        'selling_price' => 100
    ]);

    $p1 = $service->create($data1);
    $p2 = $service->create($data2);

    expect($p1->slug)->toBe('rice-bran');
    expect($p2->slug)->toBe('rice-bran-2');
    expect($p1->slug)->not->toBe($p2->slug);
});

test('bulk import creates correct products and returns error summary', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(ProductService::class);
    $result = $service->bulkCreate([
        CreateProductData::from([
            'company_id' => $company->id,
            'name' => 'Product A',
            'product_type' => ProductTypeEnum::PRODUCT,
            'selling_price' => 1000
        ]),
        CreateProductData::from([
            'company_id' => $company->id,
            'name' => 'Product B',
            'product_type' => ProductTypeEnum::PRODUCT,
            'selling_price' => 2000
        ]),
    ]);

    expect($result['created'])->toBeGreaterThanOrEqual(2);
    expect($result['failed'])->toBeGreaterThanOrEqual(0);
    expect($result)->toHaveKeys(['created', 'failed', 'errors']);
});

test('ImportProductsJob is dispatched to imports queue', function (): void {
    Queue::fake();

    $company = Company::factory()->create();
    $rows = [['name' => 'Product X', 'product_type' => 'product', 'selling_price' => 500]];

    ImportJob::dispatch($company, $rows, 1);

    Queue::assertPushedOn('imports', ImportJob::class);
});

test('alert rule evaluates when product is saved', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    // Create a "low stock" alert rule
    SharedAlertRule::withoutGlobalScopes()->create([
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
    $data = CreateProductData::from([
        'company_id' => $company->id,
        'name' => 'Trackable Product',
        'product_type' => ProductTypeEnum::PRODUCT,
        'selling_price' => 500,
    ]);
    $product = $service->create($data);

    // The DispatchesModelEvents trait fires ModelSaved → EvaluateAlertRules listener
    $log = SharedAlertLog::withoutGlobalScopes()
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
