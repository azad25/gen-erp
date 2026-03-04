<?php

use App\Domain\Accounting\Models\Account;
use App\Domain\Auth\Models\Company;
use App\Domain\Inventory\Models\StockLayer;
use App\Domain\Inventory\Models\StockLayerAllocation;
use App\Domain\Inventory\Models\StockLevel;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\InventoryValuationService;
use App\Domain\Product\Models\Product;
use App\Services\CompanyContext;
use App\Support\Enums\StockMovementType;

// ═══════════════════════════════════════════════════
// Phase 2: Inventory Valuation Tests
// ═══════════════════════════════════════════════════

function createTestProduct(int $companyId, string $name = 'Widget', string $sku = 'WDG-001'): Product
{
    return Product::withoutGlobalScopes()->create([
        'company_id' => $companyId,
        'name' => $name,
        'slug' => strtolower(str_replace(' ', '-', $name)),
        'sku' => $sku,
        'sale_price' => 50000,
        'track_inventory' => true,
    ]);
}

function createStockIn(
    int $companyId,
    int $warehouseId,
    int $productId,
    float $quantity,
    int $unitCost,
    ?string $date = null,
): StockMovement {
    $stockLevel = StockLevel::withoutGlobalScopes()
        ->where('company_id', $companyId)
        ->where('warehouse_id', $warehouseId)
        ->where('product_id', $productId)
        ->first();

    $qtyBefore = $stockLevel ? $stockLevel->quantity : 0;

    $movement = StockMovement::withoutGlobalScopes()->create([
        'company_id' => $companyId,
        'warehouse_id' => $warehouseId,
        'product_id' => $productId,
        'movement_type' => StockMovementType::PURCHASE_RECEIPT,
        'quantity' => $quantity,
        'quantity_before' => $qtyBefore,
        'quantity_after' => $qtyBefore + $quantity,
        'unit_cost' => $unitCost,
        'movement_date' => $date ?? now()->toDateString(),
    ]);

    if ($stockLevel) {
        $stockLevel->increment('quantity', $quantity);
    } else {
        StockLevel::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'reserved' => 0,
        ]);
    }

    return $movement;
}

function createStockOut(
    int $companyId,
    int $warehouseId,
    int $productId,
    float $quantity,
): StockMovement {
    $stockLevel = StockLevel::withoutGlobalScopes()
        ->where('company_id', $companyId)
        ->where('warehouse_id', $warehouseId)
        ->where('product_id', $productId)
        ->first();

    $qtyBefore = $stockLevel ? $stockLevel->quantity : 0;

    $movement = StockMovement::withoutGlobalScopes()->create([
        'company_id' => $companyId,
        'warehouse_id' => $warehouseId,
        'product_id' => $productId,
        'movement_type' => StockMovementType::SALE,
        'quantity' => -$quantity,
        'quantity_before' => $qtyBefore,
        'quantity_after' => $qtyBefore - $quantity,
        'unit_cost' => 0,
        'movement_date' => now()->toDateString(),
    ]);

    if ($stockLevel) {
        $stockLevel->decrement('quantity', $quantity);
    }

    return $movement;
}

// ───────────────────────────────────────────────────
// StockLayer Creation Tests
// ───────────────────────────────────────────────────

test('createLayer creates a stock layer from inbound movement', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Main Warehouse',
        'code' => 'WH-001',
    ]);
    $product = createTestProduct($company->id);
    $movement = createStockIn($company->id, $warehouse->id, $product->id, 100, 25000, '2026-01-15');

    $service = app(InventoryValuationService::class);
    $layer = $service->createLayer($movement);

    expect($layer)->toBeInstanceOf(StockLayer::class);
    expect($layer->quantity_in)->toBe(100.0);
    expect($layer->quantity_remaining)->toBe(100.0);
    expect($layer->unit_cost)->toBe(25000);
    expect($layer->product_id)->toBe($product->id);
    expect($layer->warehouse_id)->toBe($warehouse->id);
});

test('Multiple stock-ins create multiple layers with different costs', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Warehouse',
        'code' => 'WH-ML',
    ]);
    $product = createTestProduct($company->id, 'Multi Layer Widget', 'MLW-001');

    $service = app(InventoryValuationService::class);

    $m1 = createStockIn($company->id, $warehouse->id, $product->id, 50, 20000, '2026-01-01');
    $l1 = $service->createLayer($m1);

    $m2 = createStockIn($company->id, $warehouse->id, $product->id, 30, 25000, '2026-02-01');
    $l2 = $service->createLayer($m2);

    $m3 = createStockIn($company->id, $warehouse->id, $product->id, 20, 30000, '2026-03-01');
    $l3 = $service->createLayer($m3);

    expect(StockLayer::withoutGlobalScopes()->where('product_id', $product->id)->count())->toBe(3);
    expect($l1->unit_cost)->toBe(20000);
    expect($l2->unit_cost)->toBe(25000);
    expect($l3->unit_cost)->toBe(30000);
});

// ───────────────────────────────────────────────────
// FIFO Consumption Tests
// ───────────────────────────────────────────────────

test('FIFO consumes oldest layer first', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'FIFO Warehouse',
        'code' => 'WH-FIFO',
    ]);
    $product = createTestProduct($company->id, 'FIFO Widget', 'FIFO-001');
    $service = app(InventoryValuationService::class);

    // Layer 1: 50 @ ৳200, Layer 2: 30 @ ৳250
    $m1 = createStockIn($company->id, $warehouse->id, $product->id, 50, 20000, '2026-01-01');
    $service->createLayer($m1);

    $m2 = createStockIn($company->id, $warehouse->id, $product->id, 30, 25000, '2026-02-01');
    $service->createLayer($m2);

    // Sell 30 units — should come entirely from Layer 1 (oldest)
    $saleMovement = createStockOut($company->id, $warehouse->id, $product->id, 30);
    $cogs = $service->consumeFifo($saleMovement);

    // 30 x 20000 = 600000
    expect($cogs)->toBe(600000);

    // Layer 1 should have 20 remaining, Layer 2 untouched
    $layer1 = StockLayer::withoutGlobalScopes()
        ->where('source_movement_id', $m1->id)
        ->first();
    expect($layer1->quantity_remaining)->toBe(20.0);

    $layer2 = StockLayer::withoutGlobalScopes()
        ->where('source_movement_id', $m2->id)
        ->first();
    expect($layer2->quantity_remaining)->toBe(30.0);
});

test('FIFO spans across multiple layers correctly', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Span Warehouse',
        'code' => 'WH-SPAN',
    ]);
    $product = createTestProduct($company->id, 'Span Widget', 'SPAN-001');
    $service = app(InventoryValuationService::class);

    // Layer 1: 10 @ ৳100, Layer 2: 20 @ ৳150, Layer 3: 30 @ ৳200
    $m1 = createStockIn($company->id, $warehouse->id, $product->id, 10, 10000, '2026-01-01');
    $service->createLayer($m1);

    $m2 = createStockIn($company->id, $warehouse->id, $product->id, 20, 15000, '2026-02-01');
    $service->createLayer($m2);

    $m3 = createStockIn($company->id, $warehouse->id, $product->id, 30, 20000, '2026-03-01');
    $service->createLayer($m3);

    // Sell 25 units — consumes all of Layer 1 (10) + 15 from Layer 2
    $saleMovement = createStockOut($company->id, $warehouse->id, $product->id, 25);
    $cogs = $service->consumeFifo($saleMovement);

    // (10 * 10000) + (15 * 15000) = 100000 + 225000 = 325000
    expect($cogs)->toBe(325000);

    // Layer 1: exhausted, Layer 2: 5 remaining, Layer 3: untouched
    $l1 = StockLayer::withoutGlobalScopes()->where('source_movement_id', $m1->id)->first();
    expect($l1->quantity_remaining)->toBe(0.0);

    $l2 = StockLayer::withoutGlobalScopes()->where('source_movement_id', $m2->id)->first();
    expect($l2->quantity_remaining)->toBe(5.0);

    $l3 = StockLayer::withoutGlobalScopes()->where('source_movement_id', $m3->id)->first();
    expect($l3->quantity_remaining)->toBe(30.0);

    // Verify allocation records (COGS audit trail)
    $allocations = StockLayerAllocation::withoutGlobalScopes()
        ->where('stock_movement_id', $saleMovement->id)
        ->orderBy('id')
        ->get();
    expect($allocations)->toHaveCount(2);
    expect($allocations[0]->quantity)->toBe(10.0);
    expect($allocations[0]->unit_cost)->toBe(10000);
    expect($allocations[1]->quantity)->toBe(15.0);
    expect($allocations[1]->unit_cost)->toBe(15000);
});

test('FIFO throws on insufficient layers', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Short Warehouse',
        'code' => 'WH-SHORT',
    ]);
    $product = createTestProduct($company->id, 'Short Widget', 'SHORT-001');
    $service = app(InventoryValuationService::class);

    // Only 10 units available
    $m1 = createStockIn($company->id, $warehouse->id, $product->id, 10, 20000);
    $service->createLayer($m1);

    // Try to sell 20 — should fail
    $saleMovement = createStockOut($company->id, $warehouse->id, $product->id, 20);
    $service->consumeFifo($saleMovement);
})->throws(RuntimeException::class, 'Insufficient stock layers');

test('FIFO updates total_cost on the stock movement', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Cost Warehouse',
        'code' => 'WH-COST',
    ]);
    $product = createTestProduct($company->id, 'Cost Widget', 'COST-001');
    $service = app(InventoryValuationService::class);

    $m1 = createStockIn($company->id, $warehouse->id, $product->id, 50, 15000);
    $service->createLayer($m1);

    $saleMovement = createStockOut($company->id, $warehouse->id, $product->id, 10);
    $service->consumeFifo($saleMovement);

    $saleMovement->refresh();
    expect($saleMovement->total_cost)->toBe(150000); // 10 * 15000
});

// ───────────────────────────────────────────────────
// Weighted Average Tests
// ───────────────────────────────────────────────────

test('Weighted average computes correct cost', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'WA Warehouse',
        'code' => 'WH-WA',
    ]);
    $product = createTestProduct($company->id, 'WA Widget', 'WA-001');
    $service = app(InventoryValuationService::class);

    // Layer 1: 100 @ ৳100, Layer 2: 100 @ ৳200
    $m1 = createStockIn($company->id, $warehouse->id, $product->id, 100, 10000);
    $service->createLayer($m1);

    $m2 = createStockIn($company->id, $warehouse->id, $product->id, 100, 20000);
    $service->createLayer($m2);

    // Weighted average: (100*10000 + 100*20000) / 200 = 15000
    $avgCost = $service->weightedAverageCost($company->id, $product->id, $warehouse->id);
    expect($avgCost)->toBe(15000);
});

test('Weighted average consumption computes correct COGS', function (): void {
    $company = Company::factory()->create(['valuation_method' => 'weighted_average']);
    CompanyContext::setActive($company);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'WA Sale Warehouse',
        'code' => 'WH-WAS',
    ]);
    $product = createTestProduct($company->id, 'WA Sale Widget', 'WAS-001');
    $service = app(InventoryValuationService::class);

    // Layer 1: 100 @ ৳100, Layer 2: 100 @ ৳200  →  avg = ৳150
    $m1 = createStockIn($company->id, $warehouse->id, $product->id, 100, 10000);
    $service->createLayer($m1);

    $m2 = createStockIn($company->id, $warehouse->id, $product->id, 100, 20000);
    $service->createLayer($m2);

    $saleMovement = createStockOut($company->id, $warehouse->id, $product->id, 50);
    $cogs = $service->consumeWeightedAverage($saleMovement);

    // 50 * 15000 = 750000
    expect($cogs)->toBe(750000);
});

// ───────────────────────────────────────────────────
// Dispatch Tests (consume method)
// ───────────────────────────────────────────────────

test('consume() dispatches to FIFO by default', function (): void {
    $company = Company::factory()->create(); // default valuation_method = fifo
    CompanyContext::setActive($company);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Dispatch Warehouse',
        'code' => 'WH-DISP',
    ]);
    $product = createTestProduct($company->id, 'Dispatch Widget', 'DISP-001');
    $service = app(InventoryValuationService::class);

    $m1 = createStockIn($company->id, $warehouse->id, $product->id, 100, 12000);
    $service->createLayer($m1);

    $saleMovement = createStockOut($company->id, $warehouse->id, $product->id, 10);
    $cogs = $service->consume($saleMovement);

    expect($cogs)->toBe(120000); // 10 * 12000
});

// ───────────────────────────────────────────────────
// Valuation Report Tests
// ───────────────────────────────────────────────────

test('Valuation report returns correct totals', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Report Warehouse',
        'code' => 'WH-RPT',
    ]);
    $product = createTestProduct($company->id, 'Report Widget', 'RPT-001');
    $service = app(InventoryValuationService::class);

    $m1 = createStockIn($company->id, $warehouse->id, $product->id, 100, 10000, '2026-01-01');
    $service->createLayer($m1);

    $m2 = createStockIn($company->id, $warehouse->id, $product->id, 50, 15000, '2026-02-01');
    $service->createLayer($m2);

    // Sell 30 (consumed from layer 1)
    $saleMovement = createStockOut($company->id, $warehouse->id, $product->id, 30);
    $service->consumeFifo($saleMovement);

    $report = $service->valuationReport($company->id, $product->id, $warehouse->id);

    // Layer 1: 70 remaining * 10000 = 700000
    // Layer 2: 50 remaining * 15000 = 750000
    // Total: 120 qty, ৳14,50,000 value
    expect($report['total_quantity'])->toBe(120.0);
    expect($report['total_value'])->toBe(1450000);
    expect($report['layers'])->toHaveCount(2);
});

// ───────────────────────────────────────────────────
// Immutability Tests
// ───────────────────────────────────────────────────

test('StockLayerAllocation is immutable (cannot update)', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Immutable Warehouse',
        'code' => 'WH-IMM',
    ]);
    $product = createTestProduct($company->id, 'Immutable Widget', 'IMM-001');
    $service = app(InventoryValuationService::class);

    $m1 = createStockIn($company->id, $warehouse->id, $product->id, 50, 10000);
    $service->createLayer($m1);

    $saleMovement = createStockOut($company->id, $warehouse->id, $product->id, 10);
    $service->consumeFifo($saleMovement);

    $allocation = StockLayerAllocation::withoutGlobalScopes()->first();
    $allocation->quantity = 99;
    $allocation->save();
})->throws(RuntimeException::class, 'immutable');

test('StockLayerAllocation is immutable (cannot delete)', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Immutable Del Warehouse',
        'code' => 'WH-IMD',
    ]);
    $product = createTestProduct($company->id, 'Immutable Del Widget', 'IMD-001');
    $service = app(InventoryValuationService::class);

    $m1 = createStockIn($company->id, $warehouse->id, $product->id, 50, 10000);
    $service->createLayer($m1);

    $saleMovement = createStockOut($company->id, $warehouse->id, $product->id, 10);
    $service->consumeFifo($saleMovement);

    $allocation = StockLayerAllocation::withoutGlobalScopes()->first();
    $allocation->delete();
})->throws(RuntimeException::class, 'immutable');

// ───────────────────────────────────────────────────
// Multi-Tenancy Isolation Tests
// ───────────────────────────────────────────────────

test('Company A layers not visible to Company B', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $warehouseA = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $companyA->id,
        'name' => 'A Warehouse',
        'code' => 'WH-A',
    ]);
    $warehouseB = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $companyB->id,
        'name' => 'B Warehouse',
        'code' => 'WH-B',
    ]);

    CompanyContext::setActive($companyA);
    $productA = createTestProduct($companyA->id, 'A Widget', 'A-001');
    $m1 = createStockIn($companyA->id, $warehouseA->id, $productA->id, 100, 10000);
    app(InventoryValuationService::class)->createLayer($m1);

    CompanyContext::setActive($companyB);
    $productB = createTestProduct($companyB->id, 'B Widget', 'B-001');
    $m2 = createStockIn($companyB->id, $warehouseB->id, $productB->id, 50, 20000);
    app(InventoryValuationService::class)->createLayer($m2);

    // Company A should only see their own layers
    CompanyContext::setActive($companyA);
    expect(StockLayer::count())->toBe(1);

    CompanyContext::setActive($companyB);
    expect(StockLayer::count())->toBe(1);
});

// ───────────────────────────────────────────────────
// Valuation Method Configuration Tests
// ───────────────────────────────────────────────────

test('Product-level override takes precedence over company default', function (): void {
    // Company defaults to FIFO
    $company = Company::factory()->create(['valuation_method' => 'fifo']);
    CompanyContext::setActive($company);

    $warehouse = Warehouse::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Override Warehouse',
        'code' => 'WH-OVR',
    ]);

    // Product overrides to WEIGHTED_AVERAGE
    $product = createTestProduct($company->id, 'Override Widget', 'OVR-001');
    $product->update(['valuation_method' => 'weighted_average']);

    $service = app(InventoryValuationService::class);

    // Layer 1: 100 @ ৳100, Layer 2: 100 @ ৳200 → WA avg = ৳150
    $m1 = createStockIn($company->id, $warehouse->id, $product->id, 100, 10000);
    $service->createLayer($m1);

    $m2 = createStockIn($company->id, $warehouse->id, $product->id, 100, 20000);
    $service->createLayer($m2);

    // If it used FIFO, COGS for 50 would be 50 * 10000 = 500000
    // If it uses WA, COGS for 50 would be 50 * 15000 = 750000
    $saleMovement = createStockOut($company->id, $warehouse->id, $product->id, 50);
    $cogs = $service->consume($saleMovement);

    expect($cogs)->toBe(750000); // Expecting WA, not FIFO
});
