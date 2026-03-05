# Inventory Domain - Complete Analysis

## Overview

The inventory domain manages stock movements, warehouse operations, and inventory valuation with tight integration to accounting for COGS calculation and to sales/purchase domains for stock management.

## Backend Architecture

### 1. Core Models

#### StockLevel Model (`app/Domain/Inventory/Models/StockLevel.php`)

**Purpose:** Current stock quantity for a product/variant at a specific warehouse

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'warehouse_id',            // Warehouse (FK)
  'product_id',             // Product (FK)
  'variant_id',              // Product variant (FK)
  'quantity',                // On-hand quantity
  'reserved_quantity',      // Reserved for orders
];

// Available = on-hand minus reserved
public function availableQuantity(): float {
  return $this->quantity - $this->reserved_quantity;
}

// Low stock detection
public function isLowStock(): bool {
  $threshold = $this->product?->low_stock_threshold ?? 0;
  if ($threshold <= 0) return false;
  return $this->availableQuantity() <= $threshold;
}
```

**Relationships:**
```php
warehouse() -> Warehouse
product() -> Product
variant() -> ProductVariant
```

#### StockMovement Model (`app/Domain/Inventory/Models/StockMovement.php`)

**Purpose:** Record of every stock change (immutable audit trail)

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'idempotency_key',         // Prevent duplicate movements
  'warehouse_id',            // Warehouse (FK)
  'product_id',             // Product (FK)
  'variant_id',              // Product variant (FK)
  'movement_type',           // StockMovementType enum
  'quantity',                // Quantity moved (positive/negative)
  'quantity_before',         // Quantity before movement
  'quantity_after',          // Quantity after movement
  'unit_cost',               // Unit cost (for stock-in)
  'total_cost',              // Total COGS (for stock-out)
  'reference_type',          // Reference type (invoice, grn, etc.)
  'reference_id',            // Reference ID
  'notes',                  // Notes
  'movement_date',           // Movement date
];

// Prevent updates to stock movements
protected static function booted(): void {
  static::updating(function () {
    throw new \RuntimeException('Stock movements are immutable and cannot be updated.');
  });
}
```

**Movement Types:**
```php
enum StockMovementType: string {
  case OPENING_STOCK = 'opening_stock';      // Initial stock
  case PURCHASE_RECEIPT = 'purchase_receipt'; // Goods receipt
  case SALE_RETURN = 'sale_return';          // Sales return
  case SALE = 'sale';                        // Sales
  case PURCHASE_RETURN = 'purchase_return';  // Purchase return
  case TRANSFER_OUT = 'transfer_out';        // Transfer out
  case TRANSFER_IN = 'transfer_in';          // Transfer in
  case ADJUSTMENT_IN = 'adjustment_in';      // Adjustment in
  case ADJUSTMENT_OUT = 'adjustment_out';    // Adjustment out
}
```

#### StockLayer Model (`app/Domain/Inventory/Models/StockLayer.php`)

**Purpose:** Batch of inventory received at a specific cost (FIFO/WAC valuation)

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'warehouse_id',            // Warehouse (FK)
  'product_id',             // Product (FK)
  'variant_id',              // Product variant (FK)
  'source_movement_id',      // Source stock movement (FK)
  'quantity_in',             // Quantity in this layer
  'quantity_remaining',      // Remaining quantity
  'unit_cost',               // Unit cost
  'layer_date',             // Layer creation date
];

// Whether this layer has been fully consumed
public function isExhausted(): bool {
  return $this->quantity_remaining <= 0;
}

// Total value remaining in this layer
public function remainingValue(): int {
  return (int) round($this->quantity_remaining * $this->unit_cost);
}
```

**Relationships:**
```php
warehouse() -> Warehouse
product() -> Product
variant() -> ProductVariant
sourceMovement() -> StockMovement
allocations() -> StockLayerAllocation (hasMany)
```

#### StockLayerAllocation Model

**Purpose:** Tracks which layers were consumed for each stock-out (COGS audit trail)

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'stock_layer_id',          // Stock layer (FK)
  'stock_movement_id',       // Stock movement (FK)
  'quantity',                // Quantity consumed
  'unit_cost',               // Unit cost at time of consumption
  'cost_amount',             // Total cost for this allocation
];
```

#### Warehouse Model (`app/Domain/Inventory/Models/Warehouse.php`)

**Purpose:** Physical storage location for inventory

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'code',                    // Warehouse code
  'name',                    // Warehouse name
  'address',                 // Address
  'is_active',               // Active status
  'is_default',              // Default warehouse
];
```

**Relationships:**
```php
company() -> Company
stockLevels() -> StockLevel (hasMany)
```

#### StockAdjustment Model (`app/Domain/Inventory/Models/StockAdjustment.php`)

**Purpose:** Stock adjustment document with workflow approval support

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'warehouse_id',            // Warehouse (FK)
  'reference_number',        // Auto-generated: ADJ-YYYYMMDD-XXXX
  'reason',                  // AdjustmentReason enum
  'notes',                  // Notes
  'status',                  // StockAdjustmentStatus enum
  'adjusted_by',             // Adjusted by user (FK)
  'approved_by',             // Approved by user (FK)
  'adjustment_date',         // Adjustment date
];

// Auto-generates reference_number on creation
$reference_number = "ADJ-" . now()->format('Ymd') . "-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
```

**Workflow States:**
- `DRAFT` - Initial state
- `PENDING` - Pending approval
- `APPROVED` - Approved
- `APPLIED` - Applied to stock
- `REJECTED` - Rejected

#### StockTransfer Model (`app/Domain/Inventory/Models/StockTransfer.php`)

**Purpose:** Stock transfer between two warehouses

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'reference_number',        // Auto-generated: TRF-YYYYMMDD-XXXX
  'from_warehouse_id',       // Source warehouse (FK)
  'to_warehouse_id',         // Destination warehouse (FK)
  'status',                  // StockTransferStatus enum
  'notes',                  // Notes
  'transferred_by',          // Transferred by user (FK)
  'received_by',             // Received by user (FK)
  'transfer_date',           // Transfer date
  'received_date',           // Received date
];

// Auto-generates reference_number on creation
$reference_number = "TRF-" . now()->format('Ymd') . "-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
```

**Workflow States:**
- `DRAFT` - Initial state
- `IN_TRANSIT` - Stock moved out of source
- `RECEIVED` - Stock received at destination
- `CANCELLED` - Cancelled

### 2. Services

#### InventoryService (`app/Domain/Inventory/Services/InventoryService.php`)

**Purpose:** Central gateway for all stock changes

**Methods:**

```php
// Increase stock at a warehouse (purchase receipt, found, production in, etc.)
public function stockIn(
  int $warehouseId,
  int $productId,
  float $quantity,
  StockMovementType $type,
  ?int $variantId = null,
  ?int $unitCost = null,
  ?string $notes = null,
  ?Model $reference = null,
): StockMovement {
  return DB::transaction(function () use ($warehouseId, $productId, $quantity, $type, $variantId, $unitCost, $notes, $reference): StockMovement {
    $level = $this->getOrCreateLevel($warehouseId, $productId, $variantId);
    $quantityBefore = $level->quantity;

    $level->increment('quantity', $quantity);
    $level->refresh();

    return $this->recordMovement(
      $level, $type, $quantity, $quantityBefore, $level->quantity,
      $unitCost, $notes, $reference,
    );
  });
}

// Decrease stock at a warehouse. Throws InsufficientStockException if not enough available.
public function stockOut(
  int $warehouseId,
  int $productId,
  float $quantity,
  StockMovementType $type,
  ?int $variantId = null,
  ?string $notes = null,
  ?Model $reference = null,
): StockMovement {
  return DB::transaction(function () use ($warehouseId, $productId, $quantity, $type, $variantId, $notes, $reference): StockMovement {
    $level = $this->getOrCreateLevel($warehouseId, $productId, $variantId);
    $available = $level->availableQuantity();

    if ($quantity > $available) {
      throw new InsufficientStockException($productId, $quantity, $available, $warehouseId);
    }

    $quantityBefore = $level->quantity;
    $level->decrement('quantity', $quantity);
    $level->refresh();

    return $this->recordMovement(
      $level, $type, $quantity, $quantityBefore, $level->quantity,
      null, $notes, $reference,
    );
  });
}

// Reserve stock for an open order (prevents overselling)
public function reserve(int $warehouseId, int $productId, float $quantity, ?int $variantId = null): void {
  DB::transaction(function () use ($warehouseId, $productId, $quantity, $variantId): void {
    $level = $this->getOrCreateLevel($warehouseId, $productId, $variantId);
    $available = $level->availableQuantity();

    if ($quantity > $available) {
      throw new InsufficientStockException($productId, $quantity, $available, $warehouseId);
    }

    $level->increment('reserved_quantity', $quantity);
  });
}

// Release reservation when order is cancelled
public function releaseReservation(int $warehouseId, int $productId, float $quantity, ?int $variantId = null): void {
  DB::transaction(function () use ($warehouseId, $productId, $quantity, $variantId): void {
    $level = $this->getOrCreateLevel($warehouseId, $productId, $variantId);
    $release = min($quantity, $level->reserved_quantity);
    $level->decrement('reserved_quantity', $release);
  });
}

// Get stock levels for a product, optionally filtered by warehouse
public function getStock(int $productId, ?int $warehouseId = null): Collection {
  $query = StockLevel::where('product_id', $productId);

  if ($warehouseId !== null) {
    $query->where('warehouse_id', $warehouseId);
  }

  return $query->get();
}

// Total available quantity across all warehouses
public function totalAvailable(int $productId, ?int $variantId = null): float {
  $query = StockLevel::where('product_id', $productId);

  if ($variantId !== null) {
    $query->where('variant_id', $variantId);
  }

  $totals = $query->selectRaw('COALESCE(SUM(quantity), 0) as qty, COALESCE(SUM(reserved_quantity), 0) as reserved')
    ->first();

  return (float) $totals->qty - (float) $totals->reserved;
}

// Apply a stock adjustment — creates stock movements for each item
public function applyAdjustment(StockAdjustment $adjustment): void {
  DB::transaction(function () use ($adjustment): void {
    foreach ($adjustment->items as $item) {
      $diff = $item->adjusted_quantity - $item->current_quantity;

      if ($diff > 0) {
        $this->stockIn(
          $item->warehouse_id, $item->product_id, abs($diff),
          StockMovementType::ADJUSTMENT_IN, $item->variant_id,
          $item->unit_cost, "Adjustment {$adjustment->reference_number}",
          $adjustment,
        );
      } elseif ($diff < 0) {
        $this->stockOut(
          $item->warehouse_id, $item->product_id, abs($diff),
          StockMovementType::ADJUSTMENT_OUT, $item->variant_id,
          "Adjustment {$adjustment->reference_number}", $adjustment,
        );
      }
    }

    $adjustment->update(['status' => StockAdjustmentStatus::APPLIED]);
  });
}

// Initiate a stock transfer — moves stock out of source warehouse
public function initiateTransfer(StockTransfer $transfer): void {
  DB::transaction(function () use ($transfer): void {
    foreach ($transfer->items as $item) {
      $this->stockOut(
        $transfer->from_warehouse_id, $item->product_id, $item->quantity_sent,
        StockMovementType::TRANSFER_OUT, $item->variant_id,
        "Transfer {$transfer->reference_number}", $transfer,
      );
    }

    $transfer->update([
      'status' => StockTransferStatus::IN_TRANSIT,
      'transferred_by' => auth()->id(),
    ]);
  });
}

// Receive a stock transfer — moves stock into destination warehouse
public function receiveTransfer(StockTransfer $transfer, array $receivedQuantities): void {
  DB::transaction(function () use ($transfer, $receivedQuantities): void {
    foreach ($transfer->items as $item) {
      $received = $receivedQuantities[$item->id] ?? $item->quantity_sent;
      $item->update(['quantity_received' => $received]);

      $this->stockIn(
        $transfer->to_warehouse_id, $item->product_id, $received,
        StockMovementType::TRANSFER_IN, $item->variant_id,
        null, "Transfer {$transfer->reference_number}", $transfer,
      );
    }

    $transfer->update([
      'status' => StockTransferStatus::RECEIVED,
      'received_by' => auth()->id(),
      'received_date' => now()->toDateString(),
    ]);
  });
}

// Set opening stock for a product at a warehouse
public function setOpeningStock(int $warehouseId, int $productId, float $quantity, int $unitCost): StockMovement {
  return $this->stockIn(
    $warehouseId, $productId, $quantity,
    StockMovementType::OPENING_STOCK, null,
    $unitCost, __('Opening stock'),
  );
}

// Paginated stock movement listing with filters
public function paginateMovements(Company $company, array $filters = [], int $perPage = 15): LengthAwarePaginator {
  return StockMovement::query()
    ->where('company_id', $company->id)
    ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('reference', 'LIKE', "%{$s}%"))
    ->when($filters['movement_type'] ?? null, fn ($q, $t) => $q->where('movement_type', $t))
    ->when($filters['product_id'] ?? null, fn ($q, $id) => $q->where('product_id', $id))
    ->when($filters['warehouse_id'] ?? null, fn ($q, $id) => $q->where('warehouse_id', $id))
    ->with(['product', 'warehouse'])
    ->orderBy('movement_date', 'desc')
    ->paginate($perPage);
}

// Stock valuation report data
public function getStockValuation(Company $company, ?int $warehouseId = null): array {
  $query = StockLevel::where('stock_levels.company_id', $company->id)
    ->where('stock_levels.quantity', '>', 0)
    ->join('products', 'products.id', '=', 'stock_levels.product_id')
    ->join('warehouses', 'warehouses.id', '=', 'stock_levels.warehouse_id')
    ->select(
      'stock_levels.product_id',
      'products.name as product_name',
      'products.sku',
      'warehouses.name as warehouse',
      'stock_levels.quantity',
    );

  if ($warehouseId !== null) {
    $query->where('stock_levels.warehouse_id', $warehouseId);
  }

  return $query->get()->map(function ($row) {
    // Avg cost from latest movements
    $avgCost = (int) StockMovement::where('product_id', $row->product_id)
      ->whereNotNull('unit_cost')
      ->where('unit_cost', '>', 0)
      ->average('unit_cost');

    return [
      'product_id' => $row->product_id,
      'product_name' => $row->product_name,
      'sku' => $row->sku,
      'warehouse' => $row->warehouse,
      'quantity' => (float) $row->quantity,
      'avg_cost' => $avgCost,
      'total_value' => (int) ($row->quantity * $avgCost),
    ];
  })->all();
}
```

#### InventoryValuationService (`app/Domain/Inventory/Services/InventoryValuationService.php`)

**Purpose:** Computes COGS by consuming stock layers using FIFO or Weighted Average

**Methods:**

```php
// Create a stock layer from an inbound movement
// Called automatically after a stock-in operation
public function createLayer(StockMovement $movement): StockLayer {
  return StockLayer::create([
    'company_id' => $movement->company_id,
    'warehouse_id' => $movement->warehouse_id,
    'product_id' => $movement->product_id,
    'variant_id' => $movement->variant_id,
    'source_movement_id' => $movement->id,
    'quantity_in' => abs($movement->quantity),
    'quantity_remaining' => abs($movement->quantity),
    'unit_cost' => $movement->unit_cost,
    'layer_date' => $movement->movement_date ?? now(),
  ]);
}

// Consume layers FIFO for an outbound movement
// Returns the total COGS (in smallest currency unit)
public function consumeFifo(StockMovement $movement): int {
  $quantityNeeded = abs($movement->quantity);
  $totalCogs = 0;

  // Lock and fetch available layers, oldest first
  $layers = StockLayer::withoutGlobalScopes()
    ->where('company_id', $movement->company_id)
    ->where('product_id', $movement->product_id)
    ->where('warehouse_id', $movement->warehouse_id)
    ->when($movement->variant_id, fn ($q, $vid) => $q->where('variant_id', $vid))
    ->where('quantity_remaining', '>', 0)
    ->orderBy('layer_date')
    ->orderBy('id')
    ->lockForUpdate()
    ->get();

  $remaining = $quantityNeeded;

  foreach ($layers as $layer) {
    if ($remaining <= 0) break;

    $consume = min($remaining, $layer->quantity_remaining);
    $costForThisAllocation = (int) round($consume * $layer->unit_cost);

    // Create the allocation record (COGS audit trail)
    StockLayerAllocation::create([
      'company_id' => $movement->company_id,
      'stock_layer_id' => $layer->id,
      'stock_movement_id' => $movement->id,
      'quantity' => $consume,
      'unit_cost' => $layer->unit_cost,
      'cost_amount' => $costForThisAllocation,
    ]);

    // Reduce the layer's remaining quantity
    StockLayer::withoutGlobalScopes()
      ->where('id', $layer->id)
      ->decrement('quantity_remaining', $consume);

    $totalCogs += $costForThisAllocation;
    $remaining -= $consume;
  }

  if ($remaining > 0) {
    throw new RuntimeException(
      'Insufficient stock layers for product :id. Needed: :needed, available: :available',
      ['id' => $movement->product_id, 'needed' => $quantityNeeded, 'available' => $quantityNeeded - $remaining]
    );
  }

  // Update the movement's total_cost for COGS tracking
  StockMovement::withoutGlobalScopes()
    ->where('id', $movement->id)
    ->update(['total_cost' => $totalCogs]);

  return $totalCogs;
}

// Compute weighted average cost for a product in a warehouse
// Returns the cost per unit (in smallest currency unit)
public function weightedAverageCost(
  int $companyId,
  int $productId,
  int $warehouseId,
  ?int $variantId = null,
): int {
  $layers = StockLayer::withoutGlobalScopes()
    ->where('company_id', $companyId)
    ->where('product_id', $productId)
    ->where('warehouse_id', $warehouseId)
    ->when($variantId, fn ($q, $vid) => $q->where('variant_id', $vid))
    ->where('quantity_remaining', '>', 0)
    ->get();

  $totalValue = 0;
  $totalQuantity = 0.0;

  foreach ($layers as $layer) {
    $totalValue += (int) round($layer->quantity_remaining * $layer->unit_cost);
    $totalQuantity += $layer->quantity_remaining;
  }

  if ($totalQuantity <= 0) {
    return 0;
  }

  return (int) round($totalValue / $totalQuantity);
}

// Consume layers using weighted average for an outbound movement
// Returns the total COGS
public function consumeWeightedAverage(StockMovement $movement): int {
  $avgCost = $this->weightedAverageCost(
    $movement->company_id,
    $movement->product_id,
    $movement->warehouse_id,
    $movement->variant_id,
  );

  $totalCogs = (int) round(abs($movement->quantity) * $avgCost);

  StockMovement::withoutGlobalScopes()
    ->where('id', $movement->id)
    ->update(['total_cost' => $totalCogs]);

  return $totalCogs;
}
```

## Frontend Architecture

### 1. Inventory/Dashboard.vue

**Purpose:** Inventory overview dashboard

**Metrics Displayed:**
- Total Stock Value (with delta)
- Total Products (with delta)
- Low Stock Items (below minimum)
- Out of Stock (zero inventory)

**Charts:**
- Stock Movement Trend (7d, 30d, 90d)
- Stock by Warehouse
- Stock by Category

**Quick Actions:**
- Stock Report
- Stock Adjustment

**API Calls:**
```javascript
GET /api/v1/inventory/dashboard - Dashboard metrics
GET /api/v1/inventory/movement-trend - Trend data
```

### 2. Inventory/Stock.vue

**Purpose:** Manage stock movements

**Features:**
- List movements with columns:
  - Movement Number
  - Product
  - Warehouse
  - Type (Badge)
  - Quantity (green for in, red for out)
  - Movement Date
- Actions:
  - View
  - Delete
- Create modal with form

**Form Fields:**
- Product (required)
- Warehouse (required)
- Type (Stock In/Stock Out)
- Quantity (required)
- Movement Date
- Reason

**API Calls:**
```javascript
GET /api/v1/stock-movements - List movements
POST /api/v1/stock-movements - Create movement
DELETE /api/v1/stock-movements/{id} - Delete movement
```

### 3. Inventory/Adjustments.vue

**Purpose:** Manage stock adjustments

**Features:**
- List adjustments with columns:
  - Adjustment Number
  - Product
  - Warehouse
  - Type (Badge)
  - Quantity (green for increase, red for decrease)
  - Adjustment Date
- Actions:
  - View
  - Delete
- Create modal with form

**Form Fields:**
- Product (required)
- Warehouse (required)
- Type (Increase/Decrease)
- Quantity (required)
- Adjustment Date
- Reason

**API Calls:**
```javascript
GET /api/v1/stock-adjustments - List adjustments
POST /api/v1/stock-adjustments - Create adjustment
DELETE /api/v1/stock-adjustments/{id} - Delete adjustment
```

### 4. Inventory/Transfers.vue

**Purpose:** Manage stock transfers between warehouses

**Features:**
- List transfers with columns:
  - Transfer Number
  - From Warehouse
  - To Warehouse
  - Status (Badge)
  - Transfer Date
- Actions:
  - View
  - Initiate (draft only)
  - Receive (in_transit only)
  - Cancel (draft/in_transit only)
- Create modal with form

**Form Fields:**
- From Warehouse (required)
- To Warehouse (required)
- Transfer Date
- Items (dynamic):
  - Product
  - Quantity

**API Calls:**
```javascript
GET /api/v1/stock-transfers - List transfers
POST /api/v1/stock-transfers - Create transfer
POST /api/v1/stock-transfers/{id}/initiate - Initiate transfer
POST /api/v1/stock-transfers/{id}/receive - Receive transfer
DELETE /api/v1/stock-transfers/{id} - Cancel transfer
```

### 5. Inventory/Warehouses.vue

**Purpose:** Manage warehouses

**Features:**
- List warehouses with columns:
  - Code
  - Name
  - Address
  - Status (Active/Inactive)
- Actions:
  - View
  - Edit
  - Delete
- Create/Edit modal

**Form Fields:**
- Code (required)
- Name (required)
- Address
- Status

**API Calls:**
```javascript
GET /api/v1/warehouses - List warehouses
POST /api/v1/warehouses - Create warehouse
PUT /api/v1/warehouses/{id} - Update warehouse
DELETE /api/v1/warehouses/{id} - Delete warehouse
```

### 6. Inventory/Products.vue

**Purpose:** View inventory by product

**Features:**
- List products with columns:
  - SKU
  - Name
  - Category
  - Total Stock
  - Available Stock
  - Low Stock (Badge)
- Actions:
  - View
  - Adjust Stock
  - Transfer Stock

**API Calls:**
```javascript
GET /api/v1/inventory/products - List products with stock
GET /api/v1/inventory/products/{id}/stock - Get product stock
```

## Complete Data Flow

### Stock In Flow (Purchase Receipt)

```
User posts goods receipt
    ↓
PurchaseService::postReceipt()
    ├─→ GoodsReceipt::addStock()
    │   └─→ InventoryService::stockIn()
    │       ├─→ Get or create StockLevel
    │       ├─→ Increment quantity
    │       ├─→ Record StockMovement
    │       │   ├─→ movement_type = PURCHASE_RECEIPT
    │       │   ├─→ quantity = +quantity
    │       │   ├─→ quantity_before = old_quantity
    │       │   ├─→ quantity_after = new_quantity
    │       │   ├─→ unit_cost = unit_cost
    │       │   └─→ reference = goods_receipt
    │       └─→ InventoryValuationService::createLayer()
    │           ├─→ Create StockLayer
    │           │   ├─→ quantity_in = quantity
    │           │   ├─→ quantity_remaining = quantity
    │           │   ├─→ unit_cost = unit_cost
    │           │   └─→ layer_date = receipt_date
    │           └─→ Link to StockMovement
    └─→ Return GoodsReceiptResource
```

### Stock Out Flow (Sales Invoice)

```
User approves invoice
    ↓
SalesService::sendInvoice()
    ├─→ ApproveInvoice::execute()
    │   ├─→ Deduct stock and compute COGS
    │   │   ├─→ InventoryService::stockOut()
    │   │   │   ├─→ Get StockLevel
    │   │   │   ├─→ Check available quantity
    │   │   │   ├─→ Decrement quantity
    │   │   │   ├─→ Record StockMovement
    │   │   │   │   ├─→ movement_type = SALE
    │   │   │   │   ├─→ quantity = -quantity
    │   │   │   │   ├─→ quantity_before = old_quantity
    │   │   │   │   ├─→ quantity_after = new_quantity
    │   │   │   │   └─→ reference = invoice
    │   │   │   └─→ InventoryValuationService::consumeFifo()
    │   │   │       ├─→ Lock available layers (oldest first)
    │   │   │       ├─→ Consume layers
    │   │   │       ├─→ Create StockLayerAllocation
    │   │   │       ├─→ Reduce quantity_remaining
    │   │   │       └─→ Return totalCogs
    │   │   └─→ Build journal entry with COGS
    │   │       ├─→ DR: Accounts Receivable
    │   │       ├─→ CR: Sales Revenue
    │   │       ├─→ CR: Output VAT Payable
    │   │       ├─→ DR: Cost of Goods Sold
    │   │       └─→ CR: Inventory
    │   └─→ Post journal entry
    └─→ Return InvoiceResource
```

### Stock Transfer Flow

```
User initiates transfer
    ↓
InventoryService::initiateTransfer()
    ├─→ StockTransfer::initiate()
    │   ├─→ For each item:
    │   │   └─→ InventoryService::stockOut()
    │   │       ├─→ movement_type = TRANSFER_OUT
    │   │       ├─→ quantity = -quantity
    │   │       └─→ reference = stock_transfer
    │   └─→ Set status = IN_TRANSIT
    └─→ Return StockTransferResource

User receives transfer
    ↓
InventoryService::receiveTransfer()
    ├─→ For each item:
    │   └─→ InventoryService::stockIn()
    │       ├─→ movement_type = TRANSFER_IN
    │       ├─→ quantity = +quantity
    │       ├─→ unit_cost = null (no cost for transfer)
    │       └─→ reference = stock_transfer
    └─→ Set status = RECEIVED
```

### Stock Adjustment Flow

```
User creates adjustment
    ↓
StockAdjustment::create()
    ├─→ Generate reference_number (ADJ-YYYYMMDD-XXXX)
    ├─→ Set status = DRAFT
    └─→ Create StockAdjustmentItems
    ↓
User approves adjustment
    ↓
InventoryService::applyAdjustment()
    ├─→ For each item:
    │   ├─→ Calculate diff = adjusted - current
    │   ├─→ If diff > 0:
    │   │   └─→ InventoryService::stockIn()
    │   │       ├─→ movement_type = ADJUSTMENT_IN
    │   │       ├─→ quantity = +diff
    │   │       └─→ reference = stock_adjustment
    │   └─→ If diff < 0:
    │       └─→ InventoryService::stockOut()
    │           ├─→ movement_type = ADJUSTMENT_OUT
    │           ├─→ quantity = -diff
    │           └─→ reference = stock_adjustment
    └─→ Set status = APPLIED
```

## Integration with Accounting Domain

### COGS Calculation

**FIFO Method:**
```php
// On stock-in: Create layer
InventoryValuationService::createLayer($movement)
  ├─→ Create StockLayer
  │   ├─→ quantity_in = quantity
  │   ├─→ quantity_remaining = quantity
  │   ├─→ unit_cost = unit_cost
  │   └─→ layer_date = movement_date

// On stock-out: Consume layers FIFO
InventoryValuationService::consumeFifo($movement)
  ├─→ Lock available layers (oldest first)
  ├─→ For each layer:
  │   ├─→ consume = min(remaining, layer.quantity_remaining)
  │   ├─→ cost_for_allocation = consume * layer.unit_cost
  │   ├─→ Create StockLayerAllocation (audit trail)
  │   ├─→ Decrement layer.quantity_remaining
  │   └─→ totalCogs += cost_for_allocation
  └─→ Return totalCogs
```

**Weighted Average Method:**
```php
InventoryValuationService::weightedAverageCost($companyId, $productId, $warehouseId)
  ├─→ Fetch all available layers
  ├─→ Calculate total value = Σ(quantity_remaining * unit_cost)
  ├─→ Calculate total quantity = Σ(quantity_remaining)
  └─→ Return avg_cost = total_value / total_quantity

InventoryValuationService::consumeWeightedAverage($movement)
  ├─→ Calculate avg_cost
  ├─→ totalCogs = quantity * avg_cost
  └─→ Return totalCogs
```

### Journal Entry Structure

**Sales Invoice Approval:**
```
DR: Accounts Receivable  (total_amount)
CR: Sales Revenue        (subtotal)
CR: Output VAT Payable   (tax_amount)
DR: Cost of Goods Sold   (totalCogs)
CR: Inventory            (totalCogs)
```

**Purchase Receipt Posting:**
```
DR: Inventory            (net_amount)
DR: Input VAT Receivable (tax_amount)
CR: Accounts Payable    (total_amount)
```

## Integration with Sales Domain

### Stock Deduction on Invoice Approval

```php
ApproveInvoice::execute($invoice)
  ├─→ InventoryService::stockOut()
  │   ├─→ Check available quantity
  │   ├─→ Decrement stock level
  │   ├─→ Record stock movement (type = SALE)
  │   └─→ InventoryValuationService::consumeFifo()
  │       ├─→ Consume oldest layers
  │       ├─→ Create layer allocations
  │       └─→ Return totalCogs
  └─→ Build journal entry with COGS
      ├─→ DR: Cost of Goods Sold (totalCogs)
      └─→ CR: Inventory (totalCogs)
```

### Stock Restoration on Invoice Cancellation

```php
CancelInvoiceWithReversal::execute($invoice)
  ├─→ InventoryService::stockIn()
  │   ├─→ Increment stock level
  │   ├─→ Record stock movement (type = SALE_RETURN)
  │   └─→ InventoryValuationService::createLayer()
  │       └─→ Create new layer with original cost
  └─→ Reverse journal entry
```

### Stock Reservation on Order Confirmation

```php
SalesService::confirmOrder($order)
  ├─→ For each item with track_inventory:
  │   └─→ InventoryService::reserve()
  │       ├─→ Check available quantity
  │       └─→ Increment reserved_quantity
  └─→ Set status = CONFIRMED

SalesService::cancelOrder($order)
  ├─→ For each item with track_inventory:
  │   └─→ InventoryService::releaseReservation()
  │       └─→ Decrement reserved_quantity
  └─→ Set status = CANCELLED
```

## Integration with Purchase Domain

### Stock Addition on Goods Receipt Posting

```php
PurchaseService::postReceipt($receipt)
  ├─→ GoodsReceipt::addStock()
  │   └─→ InventoryService::stockIn()
  │       ├─→ Increment stock level
  │       ├─→ Record stock movement (type = PURCHASE_RECEIPT)
  │       ├─→ unit_cost = unit_cost
  │       └─→ InventoryValuationService::createLayer()
  │           └─→ Create StockLayer with unit_cost
  └─→ Record supplier transaction
```

### Stock Removal on Purchase Return

```php
PurchaseReturn::approve()
  ├─→ InventoryService::stockOut()
  │   ├─→ Decrement stock level
  │   ├─→ Record stock movement (type = PURCHASE_RETURN)
  │   └─→ InventoryValuationService::consumeFifo()
  │       └─→ Consume layers and return COGS
  └─→ Reverse journal entry
```

## Comparison with Modern ERPs

### Features Comparison

| Feature | This System | Odoo | Zoho |
|---------|-------------|------|------|
| **Stock Movements** | ✅ | ✅ | ✅ |
| **Stock Levels** | ✅ | ✅ | ✅ |
| **Stock Layers** | ✅ | ✅ | ✅ |
| **COGS Calculation** | ✅ (FIFO/WAC) | ✅ (FIFO/WAC) | ✅ (FIFO/WAC) |
| **Stock Reservations** | ✅ | ✅ | ✅ |
| **Stock Adjustments** | ✅ | ✅ | ✅ |
| **Stock Transfers** | ✅ | ✅ | ✅ |
| **Warehouses** | ✅ | ✅ | ✅ |
| **Multi-warehouse** | ✅ | ✅ | ✅ |
| **Stock Valuation** | ✅ | ✅ | ✅ |
| **Low Stock Alerts** | ✅ | ✅ | ✅ |
| **Batch/Lot Tracking** | ⚠️ Basic | ✅ Advanced | ✅ Advanced |
| **Serial Number Tracking** | ❌ | ✅ | ✅ |
| **Expiration Tracking** | ❌ | ✅ | ✅ |
| **Stock Forecasting** | ❌ | ✅ | ✅ |
| **Reorder Points** | ⚠️ Basic | ✅ Advanced | ✅ Advanced |
| **Multi-location** | ⚠️ Basic | ✅ Advanced | ✅ Advanced |

### Workflow Comparison

**This System:**
```
Stock Movement: Immutable audit trail
Stock Layer: FIFO/WAC consumption
Stock Transfer: DRAFT → IN_TRANSIT → RECEIVED
Stock Adjustment: DRAFT → PENDING → APPROVED → APPLIED
```

**Odoo:**
```
Stock Movement: Immutable audit trail
Stock Layer: FIFO/WAC consumption
Stock Transfer: DRAFT → IN_TRANSIT → RECEIVED
Stock Adjustment: DRAFT → PENDING → APPROVED → APPLIED
```

**Zoho:**
```
Stock Movement: Immutable audit trail
Stock Layer: FIFO/WAC consumption
Stock Transfer: DRAFT → IN_TRANSIT → RECEIVED
Stock Adjustment: DRAFT → PENDING → APPROVED → APPLIED
```

### Unique Features

**This System:**
- Bangladesh localization (BDT, Bangla numerals)
- Simplified workflow
- Idempotency guarantee
- Lock date protection
- Tight integration with accounting

**Odoo/Zoho:**
- Advanced batch/lot tracking
- Serial number tracking
- Expiration tracking
- Stock forecasting
- Reorder point automation
- Multi-location support

## API Reference

### Stock Movements

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/stock-movements` | List movements | Required |
| POST | `/api/v1/stock-movements` | Create movement | Required |
| DELETE | `/api/v1/stock-movements/{id}` | Delete movement | Required |

### Stock Adjustments

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/stock-adjustments` | List adjustments | Required |
| POST | `/api/v1/stock-adjustments` | Create adjustment | Required |
| PUT | `/api/v1/stock-adjustments/{id}` | Update adjustment | Required |
| DELETE | `/api/v1/stock-adjustments/{id}` | Delete adjustment | Required |

### Stock Transfers

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/stock-transfers` | List transfers | Required |
| POST | `/api/v1/stock-transfers` | Create transfer | Required |
| POST | `/api/v1/stock-transfers/{id}/initiate` | Initiate transfer | Required |
| POST | `/api/v1/stock-transfers/{id}/receive` | Receive transfer | Required |
| DELETE | `/api/v1/stock-transfers/{id}` | Cancel transfer | Required |

### Warehouses

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/warehouses` | List warehouses | Required |
| POST | `/api/v1/warehouses` | Create warehouse | Required |
| PUT | `/api/v1/warehouses/{id}` | Update warehouse | Required |
| DELETE | `/api/v1/warehouses/{id}` | Delete warehouse | Required |

### Query Parameters (Index)

```
search -> Filter by reference
movement_type -> Filter by movement type
product_id -> Filter by product
warehouse_id -> Filter by warehouse
per_page -> Pagination (default: 15)
page -> Page number
```

### Request Body (Create Stock Movement)

```json
{
  "warehouse_id": 1,
  "product_id": 1,
  "type": "in",
  "quantity": 10,
  "movement_date": "2026-03-05",
  "reason": "Purchase receipt"
}
```

### Response Format

```json
{
  "success": true,
  "data": {
    "id": 1,
    "warehouse": {
      "id": 1,
      "name": "Main Warehouse"
    },
    "product": {
      "id": 1,
      "name": "Product A"
    },
    "movement_type": "purchase_receipt",
    "quantity": 10,
    "quantity_before": 50,
    "quantity_after": 60,
    "movement_date": "2026-03-05"
  },
  "message": "Stock movement created"
}
```

## Frontend API Integration

### Inventory/Stock.vue

```javascript
const fetchMovements = async (page = 1) => {
  const response = await get('/stock-movements', { page, per_page: 15 })
  movements.value = response.data
  pagination.value = response.meta
}

const createMovement = async () => {
  const data = {
    warehouse_id: form.value.warehouse_id,
    product_id: form.value.product_id,
    type: form.value.type,
    quantity: form.value.quantity,
    movement_date: form.value.movement_date,
    reason: form.value.reason,
  }
  
  await post('/stock-movements', data)
  await fetchMovements()
}
```

### Inventory/Transfers.vue

```javascript
const initiateTransfer = async (transfer) => {
  await post(`/stock-transfers/${transfer.id}/initiate`)
  await fetchTransfers()
}

const receiveTransfer = async (transfer) => {
  const receivedQuantities = transfer.items.reduce((acc, item) => {
    acc[item.id] = item.quantity_sent
    return acc
  }, {})
  
  await post(`/stock-transfers/${transfer.id}/receive`, { received_quantities: receivedQuantities })
  await fetchTransfers()
}
```

## Summary

### Backend Coverage
- ✅ StockLevel model (quantity, reserved_quantity, low stock detection)
- ✅ StockMovement model (immutable audit trail)
- ✅ StockLayer model (FIFO/WAC valuation)
- ✅ StockLayerAllocation model (COGS audit trail)
- ✅ Warehouse model (multi-warehouse support)
- ✅ StockAdjustment model (workflow support)
- ✅ StockTransfer model (transfer between warehouses)
- ✅ InventoryService (stockIn, stockOut, reserve, release, transfer, adjustment)
- ✅ InventoryValuationService (FIFO/WAC COGS calculation)
- ✅ Idempotency guarantee
- ✅ Multi-tenancy support

### Frontend Coverage
- ✅ Inventory/Dashboard.vue (metrics, charts)
- ✅ Inventory/Stock.vue (list, create, delete movements)
- ✅ Inventory/Adjustments.vue (list, create, delete adjustments)
- ✅ Inventory/Transfers.vue (list, initiate, receive, cancel transfers)
- ✅ Inventory/Warehouses.vue (list, create, edit, delete warehouses)
- ✅ Inventory/Products.vue (view stock by product)
- ✅ BanglaAmount component (BDT formatting)
- ✅ Badge component (status display)
- ✅ Pagination support

### Integration
- ✅ COGS calculation (FIFO/WAC methods)
- ✅ Stock layer consumption (oldest first)
- ✅ Stock layer allocations (audit trail)
- ✅ Sales integration (stock deduction on invoice approval)
- ✅ Purchase integration (stock addition on goods receipt)
- ✅ Stock reservations (prevent overselling)
- ✅ Stock transfers (between warehouses)
- ✅ Stock adjustments (manual corrections)
- ✅ Multi-tenancy (company isolation)
- ✅ Bangladesh localization (BDT, Bangla numerals)

The inventory system provides **comprehensive stock management** that follows modern ERP patterns with tight integration to accounting, sales, and purchase domains.

## Product domain integrates with Inventory domain.

**Product Model:**
```php
use App\Domain\Inventory\Models\StockLevel;

public function stockLevels(): HasMany {
  return $this->hasMany(StockLevel::class);
}

// Field to enable/disable inventory tracking
'track_inventory' => boolean,
'low_stock_threshold' => integer,
```

**ProductType Determines Inventory Tracking:**
```php
// SERVICE and DIGITAL types never track inventory
if (!$type->tracksInventory()) {
  $data['track_inventory'] = false;
}
```

**Product Types:**
- **PHYSICAL** - Tracks inventory via StockLevel
- **SERVICE** - No inventory tracking
- **DIGITAL** - No inventory tracking

**Low Stock Detection:**
```php
ProductRepository::findLowStockForCompany($company)
  ->where('track_inventory', true)
  ->whereHas('stockLevels', fn($q) => $q->whereRaw('quantity <= reorder_level'))
```

## Relationship Summary

- **Product** has many **StockLevels** (Inventory domain)
- **StockLevel** belongs to **Product**
- Only PHYSICAL products track inventory
- Inventory domain manages stock movements, valuations, and stock levels
- Product domain manages product details, pricing, variants

They're separate domains but tightly integrated - Product defines what to track, Inventory manages the actual stock levels and movements.

## Backend Architecture
- **StockLevel Model** - Current stock quantity with reserved_quantity and low stock detection
- **StockMovement Model** - Immutable audit trail for all stock changes
- **StockLayer Model** - Batch tracking for FIFO/WAC valuation
- **StockLayerAllocation Model** - COGS audit trail
- **Warehouse Model** - Multi-warehouse support
- **StockAdjustment Model** - Workflow support (DRAFT → PENDING → APPROVED → APPLIED)
- **StockTransfer Model** - Transfer between warehouses (DRAFT → IN_TRANSIT → RECEIVED)
- **InventoryService** - stockIn, stockOut, reserve, release, transfer, adjustment operations
- **InventoryValuationService** - FIFO/WAC COGS calculation

## Data Flows
- **Stock In Flow:** Purchase receipt → stockIn → create layer → record movement
- **Stock Out Flow:** Invoice approval → stockOut → consumeFifo → calculate COGS → journal entry
- **Stock Transfer Flow:** Initiate → stockOut (TRANSFER_OUT) → receive → stockIn (TRANSFER_IN)
- **Stock Adjustment Flow:** Create → approve → stockIn/stockOut → set status APPLIED

## Integration
- **Accounting:** COGS calculation (FIFO/WAC), journal entry structure (DR: COGS, CR: Inventory)
- **Sales:** Stock deduction on invoice approval, stock restoration on cancellation, stock reservation on order confirmation
- **Purchase:** Stock addition on goods receipt posting, stock removal on purchase return

## Frontend Architecture
- **Inventory/Dashboard.vue** - Metrics (stock value, products, low stock, out of stock), charts
- **Inventory/Stock.vue** - List, create, delete movements
- **Inventory/Adjustments.vue** - List, create, delete adjustments
- **Inventory/Transfers.vue** - List, initiate, receive, cancel transfers
- **Inventory/Warehouses.vue** - List, create, edit, delete warehouses
- **Inventory/Products.vue** - View stock by product

## Comparison with Modern ERPs
- **Similar:** Core stock management, COGS calculation, stock layers, transfers, adjustments
- **Simpler:** No batch/lot tracking, no serial numbers, no expiration tracking, no forecasting
- **Unique:** Bangladesh localization, simplified workflow, idempotency guarantee, lock date protection
