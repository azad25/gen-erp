<?php

use App\Enums\AdjustmentReason;
use App\Enums\StockAdjustmentStatus;
use App\Enums\StockMovementType;
use App\Enums\StockTransferStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use App\Services\CompanyContext;
use App\Services\InventoryService;

// ═══════════════════════════════════════════════════
// InventoryTest — 12 tests
// ═══════════════════════════════════════════════════

test('stockIn creates StockMovement and increases StockLevel quantity', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id]);

    $service = app(InventoryService::class);
    $movement = $service->stockIn($warehouse->id, $product->id, 100, StockMovementType::PURCHASE_RECEIPT, null, 50000, 'First purchase');

    expect($movement)->toBeInstanceOf(StockMovement::class);
    expect($movement->quantity)->toBe(100.0);
    expect($movement->quantity_before)->toBe(0.0);
    expect($movement->quantity_after)->toBe(100.0);
    expect($movement->movement_type)->toBe(StockMovementType::PURCHASE_RECEIPT);

    $level = StockLevel::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->first();
    expect($level->quantity)->toBe(100.0);
});

test('stockOut decreases stock and throws InsufficientStockException when stock is 0', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id]);

    $service = app(InventoryService::class);
    $service->stockIn($warehouse->id, $product->id, 50, StockMovementType::PURCHASE_RECEIPT);

    // Successful stock out
    $movement = $service->stockOut($warehouse->id, $product->id, 30, StockMovementType::SALE);
    expect($movement->quantity_after)->toBe(20.0);

    // Should throw on insufficient
    $service->stockOut($warehouse->id, $product->id, 25, StockMovementType::SALE);
})->throws(InsufficientStockException::class);

test('reserve increases reserved_quantity and availableQuantity reflects this', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id]);

    $service = app(InventoryService::class);
    $service->stockIn($warehouse->id, $product->id, 100, StockMovementType::PURCHASE_RECEIPT);
    $service->reserve($warehouse->id, $product->id, 40);

    $level = StockLevel::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->first();
    expect($level->reserved_quantity)->toBe(40.0);
    expect($level->availableQuantity())->toBe(60.0);
});

test('releaseReservation decreases reserved_quantity', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id]);

    $service = app(InventoryService::class);
    $service->stockIn($warehouse->id, $product->id, 100, StockMovementType::PURCHASE_RECEIPT);
    $service->reserve($warehouse->id, $product->id, 50);
    $service->releaseReservation($warehouse->id, $product->id, 20);

    $level = StockLevel::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->first();
    expect($level->reserved_quantity)->toBe(30.0);
    expect($level->availableQuantity())->toBe(70.0);
});

test('stockOut on reserved stock cannot go below 0 available', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id]);

    $service = app(InventoryService::class);
    $service->stockIn($warehouse->id, $product->id, 100, StockMovementType::PURCHASE_RECEIPT);
    $service->reserve($warehouse->id, $product->id, 80); // 20 available

    $service->stockOut($warehouse->id, $product->id, 25, StockMovementType::SALE);
})->throws(InsufficientStockException::class);

test('StockMovement is immutable — update throws exception', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id]);

    $service = app(InventoryService::class);
    $movement = $service->stockIn($warehouse->id, $product->id, 10, StockMovementType::OPENING_STOCK);

    $movement->update(['quantity' => 999]);
})->throws(RuntimeException::class);

test('applyAdjustment writes correct stock_in and stock_out movements', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product1 = Product::factory()->create(['company_id' => $company->id]);
    $product2 = Product::factory()->create(['company_id' => $company->id]);

    $service = app(InventoryService::class);
    $service->stockIn($warehouse->id, $product1->id, 100, StockMovementType::OPENING_STOCK);
    $service->stockIn($warehouse->id, $product2->id, 50, StockMovementType::OPENING_STOCK);

    $adjustment = StockAdjustment::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'reason' => AdjustmentReason::AUDIT,
        'status' => StockAdjustmentStatus::APPROVED,
        'adjustment_date' => now()->toDateString(),
    ]);
    StockAdjustmentItem::withoutGlobalScopes()->create([
        'stock_adjustment_id' => $adjustment->id,
        'company_id' => $company->id,
        'product_id' => $product1->id,
        'warehouse_id' => $warehouse->id,
        'current_quantity' => 100,
        'adjusted_quantity' => 80, // decrease 20
    ]);
    StockAdjustmentItem::withoutGlobalScopes()->create([
        'stock_adjustment_id' => $adjustment->id,
        'company_id' => $company->id,
        'product_id' => $product2->id,
        'warehouse_id' => $warehouse->id,
        'current_quantity' => 50,
        'adjusted_quantity' => 65, // increase 15
    ]);

    $adjustment->load('items');
    $service->applyAdjustment($adjustment);

    $level1 = StockLevel::where('product_id', $product1->id)->first();
    $level2 = StockLevel::where('product_id', $product2->id)->first();
    expect($level1->quantity)->toBe(80.0);
    expect($level2->quantity)->toBe(65.0);
    expect($adjustment->fresh()->status)->toBe(StockAdjustmentStatus::APPLIED);
});

test('receiveTransfer with partial quantities only moves received amounts', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $whA = Warehouse::factory()->create(['company_id' => $company->id, 'code' => 'WH-A']);
    $whB = Warehouse::factory()->create(['company_id' => $company->id, 'code' => 'WH-B']);
    $product = Product::factory()->create(['company_id' => $company->id]);

    $service = app(InventoryService::class);
    $service->stockIn($whA->id, $product->id, 100, StockMovementType::OPENING_STOCK);

    $transfer = StockTransfer::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'from_warehouse_id' => $whA->id,
        'to_warehouse_id' => $whB->id,
        'status' => StockTransferStatus::DRAFT,
        'transfer_date' => now()->toDateString(),
    ]);
    $item = StockTransferItem::withoutGlobalScopes()->create([
        'stock_transfer_id' => $transfer->id,
        'company_id' => $company->id,
        'product_id' => $product->id,
        'quantity_sent' => 40,
    ]);
    $transfer->load('items');

    // Initiate — moves out of WH-A
    $service->initiateTransfer($transfer);
    expect(StockLevel::where('warehouse_id', $whA->id)->where('product_id', $product->id)->first()->quantity)->toBe(60.0);

    // Receive partial — only 35 of 40 received
    $service->receiveTransfer($transfer->fresh(), [$item->id => 35]);

    $levelB = StockLevel::where('warehouse_id', $whB->id)->where('product_id', $product->id)->first();
    expect($levelB->quantity)->toBe(35.0);
    expect($transfer->fresh()->status)->toBe(StockTransferStatus::RECEIVED);
});

test('totalAvailable sums across warehouses correctly', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $wh1 = Warehouse::factory()->create(['company_id' => $company->id, 'code' => 'WH-1']);
    $wh2 = Warehouse::factory()->create(['company_id' => $company->id, 'code' => 'WH-2']);
    $product = Product::factory()->create(['company_id' => $company->id]);

    $service = app(InventoryService::class);
    $service->stockIn($wh1->id, $product->id, 75, StockMovementType::OPENING_STOCK);
    $service->stockIn($wh2->id, $product->id, 25, StockMovementType::OPENING_STOCK);
    $service->reserve($wh1->id, $product->id, 10);

    // total = (75-10) + 25 = 90
    expect($service->totalAvailable($product->id))->toBe(90.0);
});

test('Company A stock cannot be read by Company B', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();
    $whA = Warehouse::factory()->create(['company_id' => $companyA->id]);
    $whB = Warehouse::factory()->create(['company_id' => $companyB->id]);
    $prodA = Product::factory()->create(['company_id' => $companyA->id]);
    $prodB = Product::factory()->create(['company_id' => $companyB->id]);

    $service = app(InventoryService::class);
    CompanyContext::setActive($companyA);
    $service->stockIn($whA->id, $prodA->id, 100, StockMovementType::OPENING_STOCK);

    CompanyContext::setActive($companyB);
    $service->stockIn($whB->id, $prodB->id, 50, StockMovementType::OPENING_STOCK);

    // Company B should only see its own stock
    expect(StockLevel::all())->toHaveCount(1);
    expect(StockMovement::all())->toHaveCount(1);

    CompanyContext::setActive($companyA);
    expect(StockLevel::all())->toHaveCount(1);
});

test('opening stock movement has correct type and quantity_before = 0', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id]);

    $service = app(InventoryService::class);
    $movement = $service->setOpeningStock($warehouse->id, $product->id, 500, 100000);

    expect($movement->movement_type)->toBe(StockMovementType::OPENING_STOCK);
    expect($movement->quantity_before)->toBe(0.0);
    expect($movement->quantity_after)->toBe(500.0);
    expect($movement->unit_cost)->toBe(100000);
});

test('stockIn to warehouse A does not affect warehouse B', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $whA = Warehouse::factory()->create(['company_id' => $company->id, 'code' => 'WH-A2']);
    $whB = Warehouse::factory()->create(['company_id' => $company->id, 'code' => 'WH-B2']);
    $product = Product::factory()->create(['company_id' => $company->id]);

    $service = app(InventoryService::class);
    $service->stockIn($whA->id, $product->id, 200, StockMovementType::PURCHASE_RECEIPT);

    $levelA = StockLevel::where('warehouse_id', $whA->id)->where('product_id', $product->id)->first();
    $levelB = StockLevel::where('warehouse_id', $whB->id)->where('product_id', $product->id)->first();

    expect($levelA->quantity)->toBe(200.0);
    expect($levelB)->toBeNull();
});
