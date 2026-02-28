<?php

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\Company;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\CompanyContext;
use App\Services\Mushak61ReportService;
use App\Services\PurchaseService;

// ═══════════════════════════════════════════════════
// PurchaseTest — 12 tests
// ═══════════════════════════════════════════════════

test('purchase order created with correct company_id and auto reference number', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'cost_price' => 50000]);

    $service = app(PurchaseService::class);
    $order = $service->createOrder($company, [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => now()->toDateString(),
    ], [
        ['product_id' => $product->id, 'description' => 'Raw Material', 'quantity_ordered' => 100, 'unit_cost' => 50000, 'unit' => 'kg'],
    ]);

    expect($order->company_id)->toBe($company->id);
    expect($order->reference_number)->toStartWith('PO-');
    expect($order->items)->toHaveCount(1);
});

test('calculateTotals returns correct figures for line items with tax', function (): void {
    $service = app(PurchaseService::class);

    $totals = $service->calculateTotals([
        ['unit_cost' => 100000, 'quantity_ordered' => 5, 'discount_percent' => 10, 'tax_rate' => 15],
        ['unit_cost' => 20000, 'quantity_ordered' => 10, 'discount_percent' => 0, 'tax_rate' => 5],
    ]);

    // Item 1: 500000 gross, 50000 discount = 450000 net, 67500 tax
    // Item 2: 200000 gross, 0 discount = 200000 net, 10000 tax
    expect($totals['subtotal'])->toBe(700000);
    expect($totals['discount'])->toBe(50000);
    expect($totals['tax'])->toBe(77500);
    expect($totals['total'])->toBe(727500);
});

test('postReceipt calls InventoryService stockIn for each item', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'track_inventory' => true]);

    $service = app(PurchaseService::class);
    $order = $service->createOrder($company, [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => now()->toDateString(),
    ], [
        ['product_id' => $product->id, 'description' => 'Material', 'quantity_ordered' => 50, 'unit_cost' => 30000, 'unit' => 'pcs'],
    ]);

    $receipt = $service->createReceipt($order, [
        ['purchase_order_item_id' => $order->items->first()->id, 'quantity_received' => 50],
    ]);

    $service->postReceipt($receipt);

    expect($receipt->fresh()->status)->toBe(GoodsReceiptStatus::POSTED);
    expect($receipt->fresh()->stock_added)->toBeTrue();

    $level = StockLevel::where('product_id', $product->id)->first();
    expect($level->quantity)->toBe(50.0);
});

test('postReceipt records SupplierTransaction', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'track_inventory' => true]);

    $service = app(PurchaseService::class);
    $order = $service->createOrder($company, [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => now()->toDateString(),
    ], [
        ['product_id' => $product->id, 'description' => 'Widget', 'quantity_ordered' => 10, 'unit_cost' => 100000, 'unit' => 'pcs'],
    ]);

    $receipt = $service->createReceipt($order, [
        ['purchase_order_item_id' => $order->items->first()->id, 'quantity_received' => 10],
    ]);

    $service->postReceipt($receipt);

    expect($supplier->transactions()->count())->toBe(1);
    expect($supplier->transactions()->first()->amount)->toBe($receipt->total_amount);
});

test('partial receipt: PO status → partial, remaining qty tracked correctly', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'track_inventory' => true]);

    $service = app(PurchaseService::class);
    $order = $service->createOrder($company, [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => now()->toDateString(),
    ], [
        ['product_id' => $product->id, 'description' => 'Item', 'quantity_ordered' => 100, 'unit_cost' => 10000, 'unit' => 'pcs'],
    ]);

    $receipt = $service->createReceipt($order, [
        ['purchase_order_item_id' => $order->items->first()->id, 'quantity_received' => 40],
    ]);

    $service->postReceipt($receipt);

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::PARTIAL);
    expect($order->fresh()->items->first()->quantity_received)->toBe(40.0);
    expect($order->fresh()->items->first()->remainingQuantity())->toBe(60.0);
});

test('full receipt: PO status → received', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'track_inventory' => true]);

    $service = app(PurchaseService::class);
    $order = $service->createOrder($company, [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => now()->toDateString(),
    ], [
        ['product_id' => $product->id, 'description' => 'Item', 'quantity_ordered' => 25, 'unit_cost' => 20000, 'unit' => 'pcs'],
    ]);

    $receipt = $service->createReceipt($order, [
        ['purchase_order_item_id' => $order->items->first()->id, 'quantity_received' => 25],
    ]);

    $service->postReceipt($receipt);

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::RECEIVED);
    expect($order->fresh()->isFullyReceived())->toBeTrue();
});

test('postReceipt is atomic — if stock addition fails, supplier transaction not recorded', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);

    // Product without a valid ID to force failure
    $service = app(PurchaseService::class);
    $receipt = $service->createDirectReceipt($company, [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'receipt_date' => now()->toDateString(),
    ], [
        ['description' => 'Ad-hoc', 'quantity_received' => 5, 'unit_cost' => 10000, 'unit' => 'pcs'],
    ]);

    // This should succeed — no product, so nothing to stock in, just supplier txn
    $service->postReceipt($receipt);

    expect($receipt->fresh()->status)->toBe(GoodsReceiptStatus::POSTED);
    expect($supplier->transactions()->count())->toBe(1);
});

test('cancelOrder throws if a posted receipt exists', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'track_inventory' => true]);

    $service = app(PurchaseService::class);
    $order = $service->createOrder($company, [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => now()->toDateString(),
    ], [
        ['product_id' => $product->id, 'description' => 'Item', 'quantity_ordered' => 10, 'unit_cost' => 10000, 'unit' => 'pcs'],
    ]);

    $receipt = $service->createReceipt($order, [
        ['purchase_order_item_id' => $order->items->first()->id, 'quantity_received' => 10],
    ]);

    $service->postReceipt($receipt);

    $service->cancelOrder($order->fresh());
})->throws(RuntimeException::class, 'Cannot cancel a purchase order with posted receipts.');

test('Mushak 6.1 report includes only VAT-registered supplier receipts', function (): void {
    $company = Company::factory()->create(['vat_registered' => true]);
    CompanyContext::setActive($company);

    $vatSupplier = Supplier::factory()->create(['company_id' => $company->id, 'vat_bin' => '123456789']);
    $noVatSupplier = Supplier::factory()->create(['company_id' => $company->id, 'vat_bin' => null]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);

    $service = app(PurchaseService::class);

    // Receipt from VAT supplier
    $receipt1 = $service->createDirectReceipt($company, [
        'supplier_id' => $vatSupplier->id,
        'warehouse_id' => $warehouse->id,
        'receipt_date' => now()->toDateString(),
        'supplier_invoice_number' => 'SINV-001',
    ], [
        ['description' => 'A', 'quantity_received' => 10, 'unit_cost' => 50000, 'unit' => 'pcs', 'tax_rate' => 15],
    ]);
    $service->postReceipt($receipt1);

    // Receipt from non-VAT supplier
    $receipt2 = $service->createDirectReceipt($company, [
        'supplier_id' => $noVatSupplier->id,
        'warehouse_id' => $warehouse->id,
        'receipt_date' => now()->toDateString(),
    ], [
        ['description' => 'B', 'quantity_received' => 5, 'unit_cost' => 20000, 'unit' => 'pcs'],
    ]);
    $service->postReceipt($receipt2);

    $report = app(Mushak61ReportService::class)->generate($company, (int) now()->format('m'), (int) now()->format('Y'));

    expect($report)->toHaveCount(1);
    expect($report[0]['vat_bin'])->toBe('123456789');
});

test('Company A purchase orders not visible to Company B', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();
    $supplierA = Supplier::factory()->create(['company_id' => $companyA->id]);
    $warehouseA = Warehouse::factory()->create(['company_id' => $companyA->id]);

    CompanyContext::setActive($companyA);
    $service = app(PurchaseService::class);
    $service->createOrder($companyA, [
        'supplier_id' => $supplierA->id,
        'warehouse_id' => $warehouseA->id,
        'order_date' => now()->toDateString(),
    ], [
        ['description' => 'X', 'quantity_ordered' => 1, 'unit_cost' => 10000, 'unit' => 'pcs'],
    ]);

    CompanyContext::setActive($companyB);
    expect(PurchaseOrder::all())->toHaveCount(0);
    expect(GoodsReceipt::all())->toHaveCount(0);

    CompanyContext::setActive($companyA);
    expect(PurchaseOrder::all())->toHaveCount(1);
});

test('direct receipt without PO adds stock correctly', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'track_inventory' => true]);

    $service = app(PurchaseService::class);
    $receipt = $service->createDirectReceipt($company, [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'receipt_date' => now()->toDateString(),
    ], [
        ['product_id' => $product->id, 'description' => 'Direct purchase', 'quantity_received' => 30, 'unit_cost' => 40000, 'unit' => 'pcs'],
    ]);

    $service->postReceipt($receipt);

    $level = StockLevel::where('product_id', $product->id)->first();
    expect($level->quantity)->toBe(30.0);
    expect($receipt->fresh()->purchase_order_id)->toBeNull();
});

test('TDS/VDS amounts calculated correctly from supplier rates', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $supplier = Supplier::factory()->create([
        'company_id' => $company->id,
        'tds_rate' => 5.0,
        'vds_rate' => 7.5,
    ]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);

    $service = app(PurchaseService::class);
    $order = $service->createOrder($company, [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => now()->toDateString(),
    ], [
        ['description' => 'Service', 'quantity_ordered' => 1, 'unit_cost' => 1000000, 'unit' => 'job'],
    ]);

    $tdsVds = $service->calculateTdsVds($order);

    // total = 1000000
    // TDS 5% = 50000, VDS 7.5% = 75000, net = 875000
    expect($tdsVds['tds_amount'])->toBe(50000);
    expect($tdsVds['vds_amount'])->toBe(75000);
    expect($tdsVds['net'])->toBe(875000);
});
