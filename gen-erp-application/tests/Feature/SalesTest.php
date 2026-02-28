<?php

use App\Enums\InvoiceStatus;
use App\Enums\SalesOrderStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\StockLevel;
use App\Models\Warehouse;
use App\Services\CompanyContext;
use App\Services\InventoryService;
use App\Services\SalesService;

// ═══════════════════════════════════════════════════
// SalesTest — 13 tests
// ═══════════════════════════════════════════════════

test('sales order created with correct company_id and auto reference number', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'selling_price' => 100000]);

    $service = app(SalesService::class);
    $order = $service->createOrder($company, [
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => now()->toDateString(),
    ], [
        ['product_id' => $product->id, 'description' => 'Widget', 'quantity' => 2, 'unit_price' => 100000, 'unit' => 'pcs'],
    ]);

    expect($order->company_id)->toBe($company->id);
    expect($order->reference_number)->toStartWith('SO-');
    expect($order->items)->toHaveCount(1);
});

test('calculateTotals returns correct subtotal, tax, and total for mixed-tax items', function (): void {
    $service = app(SalesService::class);

    $totals = $service->calculateTotals([
        ['unit_price' => 100000, 'quantity' => 2, 'discount_percent' => 10, 'tax_rate' => 15],
        ['unit_price' => 50000, 'quantity' => 3, 'discount_percent' => 0, 'tax_rate' => 5],
    ], true);

    // Item 1: 200000 gross, 20000 discount = 180000 net, 27000 tax
    // Item 2: 150000 gross, 0 discount = 150000 net, 7500 tax
    expect($totals['subtotal'])->toBe(350000);
    expect($totals['discount'])->toBe(20000);
    expect($totals['tax'])->toBe(34500);
    expect($totals['total'])->toBe(364500);
});

test('VAT calculated only when company.vat_registered = true', function (): void {
    $service = app(SalesService::class);

    $withVat = $service->calculateTotals([
        ['unit_price' => 100000, 'quantity' => 1, 'tax_rate' => 15],
    ], true);

    $withoutVat = $service->calculateTotals([
        ['unit_price' => 100000, 'quantity' => 1, 'tax_rate' => 15],
    ], false);

    expect($withVat['tax'])->toBe(15000);
    expect($withoutVat['tax'])->toBe(0);
    expect($withVat['total'])->toBe(115000);
    expect($withoutVat['total'])->toBe(100000);
});

test('confirmOrder reserves stock via InventoryService', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'track_inventory' => true]);

    // Stock in 100 units
    app(InventoryService::class)->stockIn($warehouse->id, $product->id, 100, StockMovementType::OPENING_STOCK);

    $service = app(SalesService::class);
    $order = $service->createOrder($company, [
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => now()->toDateString(),
    ], [
        ['product_id' => $product->id, 'description' => 'Widget', 'quantity' => 30, 'unit_price' => 50000, 'unit' => 'pcs'],
    ]);

    $order->load('items');
    $service->confirmOrder($order);

    expect($order->fresh()->status)->toBe(SalesOrderStatus::CONFIRMED);
    $level = StockLevel::where('product_id', $product->id)->first();
    expect($level->reserved_quantity)->toBe(30.0);
    expect($level->availableQuantity())->toBe(70.0);
});

test('cancelOrder releases stock reservations', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'track_inventory' => true]);

    app(InventoryService::class)->stockIn($warehouse->id, $product->id, 100, StockMovementType::OPENING_STOCK);

    $service = app(SalesService::class);
    $order = $service->createOrder($company, [
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => now()->toDateString(),
    ], [
        ['product_id' => $product->id, 'description' => 'Widget', 'quantity' => 25, 'unit_price' => 50000, 'unit' => 'pcs'],
    ]);

    $order->load('items');
    $service->confirmOrder($order);
    $service->cancelOrder($order->fresh());

    expect($order->fresh()->status)->toBe(SalesOrderStatus::CANCELLED);
    $level = StockLevel::where('product_id', $product->id)->first();
    expect($level->reserved_quantity)->toBe(0.0);
    expect($level->availableQuantity())->toBe(100.0);
});

test('convertToInvoice creates invoice with matching line items', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'selling_price' => 80000]);

    $service = app(SalesService::class);
    $order = $service->createOrder($company, [
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => now()->toDateString(),
    ], [
        ['product_id' => $product->id, 'description' => 'Gadget', 'quantity' => 3, 'unit_price' => 80000, 'unit' => 'pcs'],
    ]);

    $order->load('items');
    $invoice = $service->convertToInvoice($order);

    expect($invoice->sales_order_id)->toBe($order->id);
    expect($invoice->items)->toHaveCount(1);
    expect($invoice->total_amount)->toBe($order->total_amount);
    expect($invoice->items->first()->description)->toBe('Gadget');
});

test('sendInvoice deducts stock and records CustomerTransaction', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'track_inventory' => true]);

    app(InventoryService::class)->stockIn($warehouse->id, $product->id, 50, StockMovementType::OPENING_STOCK);

    $service = app(SalesService::class);
    $invoice = $service->createInvoice($company, [
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
    ], [
        ['product_id' => $product->id, 'description' => 'Product A', 'quantity' => 10, 'unit_price' => 100000, 'unit' => 'pcs'],
    ]);

    $service->sendInvoice($invoice);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::SENT);
    expect($invoice->fresh()->stock_deducted)->toBeTrue();

    $level = StockLevel::where('product_id', $product->id)->first();
    expect($level->quantity)->toBe(40.0);

    // Customer transaction recorded
    expect($customer->transactions()->count())->toBe(1);
    expect($customer->transactions()->first()->amount)->toBe($invoice->total_amount);
});

test('sendInvoice throws InsufficientStockException when stock is insufficient', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'track_inventory' => true]);

    app(InventoryService::class)->stockIn($warehouse->id, $product->id, 5, StockMovementType::OPENING_STOCK);

    $service = app(SalesService::class);
    $invoice = $service->createInvoice($company, [
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
    ], [
        ['product_id' => $product->id, 'description' => 'Product B', 'quantity' => 20, 'unit_price' => 50000, 'unit' => 'pcs'],
    ]);

    $service->sendInvoice($invoice);
})->throws(InsufficientStockException::class);

test('isOverdue returns true when due_date < today and unpaid', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);

    $invoice = Invoice::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_date' => now()->subDays(45)->toDateString(),
        'due_date' => now()->subDays(15)->toDateString(),
        'status' => InvoiceStatus::SENT,
        'total_amount' => 100000,
    ]);

    expect($invoice->isOverdue())->toBeTrue();

    // Paid invoice is NOT overdue
    $invoice->update(['status' => InvoiceStatus::PAID]);
    expect($invoice->isOverdue())->toBeFalse();
});

test('cancelInvoice reverses stock and records negative customer transaction', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'track_inventory' => true]);

    app(InventoryService::class)->stockIn($warehouse->id, $product->id, 50, StockMovementType::OPENING_STOCK);

    $service = app(SalesService::class);
    $invoice = $service->createInvoice($company, [
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
    ], [
        ['product_id' => $product->id, 'description' => 'Item', 'quantity' => 10, 'unit_price' => 50000, 'unit' => 'pcs'],
    ]);

    $service->sendInvoice($invoice);
    $service->cancelInvoice($invoice->fresh());

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::CANCELLED);
    $level = StockLevel::where('product_id', $product->id)->first();
    expect($level->quantity)->toBe(50.0); // restored

    // Two customer transactions: invoice + cancellation
    expect($customer->transactions()->count())->toBe(2);
});

test('Company A invoices cannot be read by Company B', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();
    $custA = Customer::factory()->create(['company_id' => $companyA->id]);
    $whA = Warehouse::factory()->create(['company_id' => $companyA->id]);

    CompanyContext::setActive($companyA);
    $service = app(SalesService::class);
    $service->createInvoice($companyA, [
        'customer_id' => $custA->id,
        'warehouse_id' => $whA->id,
    ], [
        ['description' => 'X', 'quantity' => 1, 'unit_price' => 10000, 'unit' => 'pcs'],
    ]);

    CompanyContext::setActive($companyB);
    expect(Invoice::all())->toHaveCount(0);
    expect(SalesOrder::all())->toHaveCount(0);

    CompanyContext::setActive($companyA);
    expect(Invoice::all())->toHaveCount(1);
});

test('service type product does not trigger stock deduction in sendInvoice', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $serviceProduct = Product::factory()->create([
        'company_id' => $company->id,
        'track_inventory' => false,
    ]);

    $service = app(SalesService::class);
    $invoice = $service->createInvoice($company, [
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
    ], [
        ['product_id' => $serviceProduct->id, 'description' => 'Consultation', 'quantity' => 1, 'unit_price' => 500000, 'unit' => 'hr'],
    ]);

    // sendInvoice should not throw even though there's no stock
    $service->sendInvoice($invoice);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::SENT);
    expect($invoice->fresh()->stock_deducted)->toBeTrue();
});

test('invoice auto-generates unique invoice_number', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);

    $service = app(SalesService::class);
    $inv1 = $service->createInvoice($company, [
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
    ], [
        ['description' => 'A', 'quantity' => 1, 'unit_price' => 10000, 'unit' => 'pcs'],
    ]);

    $inv2 = $service->createInvoice($company, [
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
    ], [
        ['description' => 'B', 'quantity' => 1, 'unit_price' => 20000, 'unit' => 'pcs'],
    ]);

    expect($inv1->invoice_number)->toStartWith('INV-');
    expect($inv2->invoice_number)->toStartWith('INV-');
    expect($inv1->invoice_number)->not->toBe($inv2->invoice_number);
});
