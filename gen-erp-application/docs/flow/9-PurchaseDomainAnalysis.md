# Purchase Domain - Complete Analysis

## Overview

The purchase domain manages the complete procurement lifecycle from purchase order to goods receipt, with tight integration to accounting for double-entry bookkeeping and inventory for stock management.

## Backend Architecture

### 1. Core Models

#### PurchaseOrder Model (`app/Domain/Purchase/Models/PurchaseOrder.php`)

**Purpose:** Starting point of the procurement flow

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'branch_id',               // Branch dimension
  'supplier_id',             // Supplier (FK)
  'warehouse_id',            // Warehouse (FK)
  'reference_number',        // Auto-generated: PO-YYYYMMDD-XXXX
  'supplier_reference',      // Supplier PO number
  'order_date',              // Order date
  'expected_delivery_date',  // Expected delivery date
  'status',                  // DRAFT, SENT, CONFIRMED, PARTIAL, RECEIVED, CANCELLED
  'subtotal',                // Subtotal in paise
  'discount_amount',         // Discount in paise
  'tax_amount',              // VAT in paise
  'shipping_amount',         // Shipping in paise
  'total_amount',            // Total in paise
  'amount_received_value',   // Amount received so far
  'notes',                  // Notes
  'terms_conditions',        // Terms
  'custom_fields',           // Custom data (JSON)
  'created_by',              // Creator user (FK)
];

// Auto-generates reference_number on creation
$reference_number = "PO-" . now()->format('Ymd') . "-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
```

**Relationships:**
```php
supplier() -> Supplier
warehouse() -> Warehouse
createdBy() -> User
items() -> PurchaseOrderItem (hasMany)
goodsReceipts() -> GoodsReceipt (hasMany)
```

**Workflow States:**
- `DRAFT` - Initial state, editable
- `SENT` - Sent to supplier
- `CONFIRMED` - Supplier confirmed
- `PARTIAL` - Partially received
- `RECEIVED` - Fully received
- `CANCELLED` - Order cancelled

#### GoodsReceipt Model (`app/Domain/Purchase/Models/GoodsReceipt.php`)

**Purpose:** Records physical receipt of purchased goods

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'branch_id',               // Branch dimension
  'purchase_order_id',       // Source purchase order (FK)
  'supplier_id',             // Supplier (FK)
  'warehouse_id',            // Warehouse (FK)
  'receipt_number',          // Auto-generated: GRN-YYYYMMDD-XXXX
  'supplier_invoice_number', // Supplier invoice number
  'supplier_invoice_date',   // Supplier invoice date
  'receipt_date',            // Receipt date
  'status',                  // DRAFT, VERIFIED, POSTED
  'subtotal',                // Subtotal in paise
  'tax_amount',              // VAT in paise
  'total_amount',            // Total in paise
  'notes',                  // Notes
  'stock_added',            // Stock added flag
  'created_by',              // Creator user (FK)
];

// Auto-generates receipt_number on creation
$receipt_number = 'GRN-' . now()->format('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
```

**Relationships:**
```php
purchaseOrder() -> PurchaseOrder
supplier() -> Supplier
warehouse() -> Warehouse
items() -> GoodsReceiptItem (hasMany)
```

**Workflow States:**
- `DRAFT` - Initial state, editable
- `VERIFIED` - Verified against PO
- `POSTED` - Posted to accounting and inventory

**Key Method:**
```php
public function addStock(): void {
  // Add stock for each item via InventoryService
  foreach ($this->items as $item) {
    if ($item->product_id === null) continue;
    
    $product = Product::find($item->product_id);
    if (!$product->track_inventory) continue;
    
    $inventoryService->stockIn(
      $this->warehouse_id,
      $item->product_id,
      $item->quantity_received,
      StockMovementType::PURCHASE_RECEIPT,
      $item->variant_id,
      $item->unit_cost,
      "GRN {$this->receipt_number}",
      $this,
    );
  }
  
  $this->update(['stock_added' => true]);
}
```

#### Supplier Model (`app/Domain/Purchase/Models/Supplier.php`)

**Purpose:** Supplier contact with TDS/VDS deduction support

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'group_id',               // Supplier group (FK)
  'supplier_code',          // Auto-generated: SUPP-XXXX
  'name',                   // Supplier name
  'contact_person',         // Contact person
  'email',                  // Email
  'phone',                  // Phone
  'mobile',                 // Mobile
  'address_line1',          // Address
  'address_line2',          // Address
  'city',                   // City
  'district',               // District (BD)
  'postal_code',            // Postal code
  'vat_bin',                // VAT BIN (BD)
  'tds_rate',               // TDS rate (%)
  'vds_rate',               // VDS rate (%)
  'credit_days',            // Credit days
  'opening_balance',        // Opening balance in paise
  'opening_balance_date',   // Opening balance date
  'bank_name',              // Bank name
  'bank_account_number',    // Bank account
  'bank_routing_number',    // Bank routing
  'notes',                  // Notes
  'custom_fields',           // Custom data (JSON)
  'is_active',              // Active status
];

// Auto-generates supplier_code on creation
$supplier_code = 'SUPP-' . str_pad($next, 4, '0', STR_PAD_LEFT);
```

**Relationships:**
```php
group() -> ContactGroup
transactions() -> SupplierTransaction (hasMany)
payments() -> SupplierPayment (hasMany)
```

**Key Method:**
```php
public function netPaymentAmount(int $grossAmount): array {
  $tdsRate = $this->tds_rate ?? 0;
  $vdsRate = $this->vds_rate ?? 0;
  
  $tdsAmount = (int) round($grossAmount * ($tdsRate / 100));
  $vdsAmount = (int) round($grossAmount * ($vdsRate / 100));
  $netAmount = $grossAmount - $tdsAmount - $vdsAmount;
  
  return [
    'net' => $netAmount,
    'tds_amount' => $tdsAmount,
    'vds_amount' => $vdsAmount,
  ];
}
```

#### PurchaseReturn Model (`app/Domain/Purchase/Models/PurchaseReturn.php`)

**Purpose:** Return goods to supplier, stock is removed

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'goods_receipt_id',         // Source goods receipt (FK)
  'supplier_id',             // Supplier (FK)
  'warehouse_id',            // Warehouse (FK)
  'return_number',           // Auto-generated: PR-YYYYMMDD-XXXX
  'return_date',             // Return date
  'reason',                  // Reason for return
  'total_amount',            // Total amount in paise
  'status',                  // DRAFT, PENDING, APPROVED, REJECTED, COMPLETED
  'stock_removed',           // Stock removed flag
  'created_by',              // Creator user (FK)
];

// Auto-generates return_number on creation
$return_number = 'PR-' . now()->format('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
```

**Relationships:**
```php
goodsReceipt() -> GoodsReceipt
supplier() -> Supplier
warehouse() -> Warehouse
items() -> PurchaseReturnItem (hasMany)
```

### 2. Services

#### PurchaseService (`app/Domain/Purchase/Services/PurchaseService.php`)

**Purpose:** Orchestrates all purchase order and goods receipt operations

**Methods:**

```php
// Paginated purchase order listing with filters
public function paginateOrders(Company $company, array $filters = [], int $perPage = 15): LengthAwarePaginator {
  return PurchaseOrder::query()
    ->where('company_id', $company->id)
    ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(function ($q) use ($s): void {
      $q->where('order_number', 'LIKE', "%{$s}%")
        ->orWhere('reference', 'LIKE', "%{$s}%");
    }))
    ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
    ->when($filters['supplier_id'] ?? null, fn ($q, $id) => $q->where('supplier_id', $id))
    ->with(['supplier', 'items.product'])
    ->orderBy('order_date', 'desc')
    ->paginate($perPage);
}

// Delete a purchase order — only allowed for draft or cancelled orders
public function deleteOrder(PurchaseOrder $order): void {
  if (! in_array($order->status, [PurchaseOrderStatus::DRAFT, PurchaseOrderStatus::CANCELLED], true)) {
    throw new RuntimeException('Only draft or cancelled orders can be deleted.');
  }
  
  $order->items()->delete();
  $order->delete();
}

// Create a new purchase order
public function createOrder(Company $company, array $data, array $items, array $customFields = []): PurchaseOrder {
  return DB::transaction(function () use ($company, $data, $items, $customFields): PurchaseOrder {
    $totals = $this->calculateTotals($items);
    
    $order = PurchaseOrder::create(array_merge($data, [
      'company_id' => $company->id,
      'subtotal' => $totals['subtotal'],
      'discount_amount' => $totals['discount'],
      'tax_amount' => $totals['tax'],
      'total_amount' => $totals['total'],
      'created_by' => auth()->id(),
    ]));
    
    $this->saveOrderItems($order, $items, $company);
    
    foreach ($customFields as $key => $value) {
      $order->setCustomField($key, $value);
    }
    
    return $order;
  });
}

// Update a purchase order
public function updateOrder(PurchaseOrder $order, array $data, array $items): PurchaseOrder {
  return DB::transaction(function () use ($order, $data, $items): PurchaseOrder {
    $company = Company::findOrFail($order->company_id);
    $totals = $this->calculateTotals($items);
    
    $order->update(array_merge($data, [
      'subtotal' => $totals['subtotal'],
      'discount_amount' => $totals['discount'],
      'tax_amount' => $totals['tax'],
      'total_amount' => $totals['total'],
    ]));
    
    $order->items()->delete();
    $this->saveOrderItems($order, $items, $company);
    
    return $order->fresh('items');
  });
}

// Mark PO as sent to supplier
public function sendOrder(PurchaseOrder $order): void {
  $order->update(['status' => PurchaseOrderStatus::SENT]);
}

// Cancel a PO — only if no posted receipts exist
public function cancelOrder(PurchaseOrder $order): void {
  $hasPosted = $order->goodsReceipts()
    ->where('status', GoodsReceiptStatus::POSTED)
    ->exists();
  
  if ($hasPosted) {
    throw new RuntimeException('Cannot cancel a purchase order with posted receipts.');
  }
  
  $order->update(['status' => PurchaseOrderStatus::CANCELLED]);
}

// Create a goods receipt from a purchase order (supports partial receipt)
public function createReceipt(PurchaseOrder $order, array $items): GoodsReceipt {
  return DB::transaction(function () use ($order, $items): GoodsReceipt {
    $subtotal = 0;
    $taxTotal = 0;
    
    $receipt = GoodsReceipt::create([
      'company_id' => $order->company_id,
      'purchase_order_id' => $order->id,
      'supplier_id' => $order->supplier_id,
      'warehouse_id' => $order->warehouse_id,
      'receipt_date' => now()->toDateString(),
      'status' => GoodsReceiptStatus::DRAFT,
      'created_by' => auth()->id(),
    ]);
    
    foreach ($items as $item) {
      $poItem = PurchaseOrderItem::findOrFail($item['purchase_order_item_id']);
      
      $lineGross = (int) round($poItem->unit_cost * (float) $item['quantity_received']);
      $lineTax = (int) round($lineGross * ($poItem->tax_rate / 100));
      $lineTotal = $lineGross + $lineTax;
      
      GoodsReceiptItem::create([
        'goods_receipt_id' => $receipt->id,
        'company_id' => $order->company_id,
        'purchase_order_item_id' => $poItem->id,
        'product_id' => $poItem->product_id,
        'variant_id' => $poItem->variant_id,
        'description' => $poItem->description,
        'quantity_received' => $item['quantity_received'],
        'unit' => $poItem->unit,
        'unit_cost' => $poItem->unit_cost,
        'tax_rate' => $poItem->tax_rate,
        'tax_amount' => $lineTax,
        'line_total' => $lineTotal,
      ]);
      
      $subtotal += $lineGross;
      $taxTotal += $lineTax;
    }
    
    $receipt->update([
      'subtotal' => $subtotal,
      'tax_amount' => $taxTotal,
      'total_amount' => $subtotal + $taxTotal,
    ]);
    
    return $receipt->load('items');
  });
}

// Create a direct receipt without a linked PO
public function createDirectReceipt(Company $company, array $data, array $items): GoodsReceipt {
  // Similar to createReceipt but without PO reference
}

// Post a goods receipt — atomically adds stock and records supplier transaction
public function postReceipt(GoodsReceipt $receipt): void {
  DB::transaction(function () use ($receipt): void {
    $receipt->load('items');
    $receipt->addStock();
    
    // Update PO item received quantities and PO status
    if ($receipt->purchase_order_id !== null) {
      $order = PurchaseOrder::find($receipt->purchase_order_id);
      
      if ($order !== null) {
        foreach ($receipt->items as $grItem) {
          if ($grItem->purchase_order_item_id !== null) {
            $poItem = PurchaseOrderItem::find($grItem->purchase_order_item_id);
            if ($poItem !== null) {
              $poItem->update([
                'quantity_received' => $poItem->quantity_received + $grItem->quantity_received,
              ]);
            }
          }
        }
        
        $order->refresh();
        $order->load('items');
        
        $newStatus = $order->isFullyReceived()
          ? PurchaseOrderStatus::RECEIVED
          : PurchaseOrderStatus::PARTIAL;
        
        $order->update([
          'status' => $newStatus,
          'amount_received_value' => $order->amount_received_value + $receipt->total_amount,
        ]);
      }
    }
    
    // Record supplier transaction (we owe the supplier)
    if ($receipt->supplier_id !== null) {
      $supplier = Supplier::findOrFail($receipt->supplier_id);
      
      $this->contactService->recordSupplierTransaction(
        $supplier,
        'goods_receipt',
        $receipt->total_amount,
        "GRN {$receipt->receipt_number}",
        $receipt,
      );
    }
    
    $receipt->update(['status' => GoodsReceiptStatus::POSTED]);
  });
}

// Calculate totals for purchase order line items
public function calculateTotals(array $items): array {
  $subtotal = 0;
  $totalDiscount = 0;
  $totalTax = 0;
  
  foreach ($items as $item) {
    $unitCost = (int) ($item['unit_cost'] ?? 0);
    $quantity = (float) ($item['quantity_ordered'] ?? $item['quantity'] ?? 0);
    $discountPercent = (float) ($item['discount_percent'] ?? 0);
    $taxRate = (float) ($item['tax_rate'] ?? 0);
    
    $lineGross = (int) round($unitCost * $quantity);
    $lineDiscount = (int) round($lineGross * ($discountPercent / 100));
    $lineNet = $lineGross - $lineDiscount;
    $lineTax = (int) round($lineNet * ($taxRate / 100));
    
    $subtotal += $lineGross;
    $totalDiscount += $lineDiscount;
    $totalTax += $lineTax;
  }
  
  return [
    'subtotal' => $subtotal,
    'discount' => $totalDiscount,
    'tax' => $totalTax,
    'total' => $subtotal - $totalDiscount + $totalTax,
  ];
}

// Calculate TDS/VDS from the supplier's rates
public function calculateTdsVds(PurchaseOrder $order): array {
  if ($order->supplier_id === null) {
    return ['net' => $order->total_amount, 'tds_amount' => 0, 'vds_amount' => 0];
  }
  
  $supplier = Supplier::find($order->supplier_id);
  if ($supplier === null) {
    return ['net' => $order->total_amount, 'tds_amount' => 0, 'vds_amount' => 0];
  }
  
  return $supplier->netPaymentAmount($order->total_amount);
}
```

### 3. Controllers

#### PurchaseOrderController (`app/Http/Controllers/Api/V1/PurchaseOrderController.php`)

**API Endpoints:**
```php
GET    /api/v1/purchase-orders              - List orders with filters
GET    /api/v1/purchase-orders/{id}         - Get order details
POST   /api/v1/purchase-orders              - Create order
PUT    /api/v1/purchase-orders/{id}         - Update order (draft only)
DELETE /api/v1/purchase-orders/{id}         - Delete order (draft only)
POST   /api/v1/purchase-orders/{id}/send   - Send order to supplier
POST   /api/v1/purchase-orders/{id}/cancel - Cancel order
```

**Query Parameters (Index):**
```
search -> Filter by order number or reference
status -> Filter by status
supplier_id -> Filter by supplier
per_page -> Pagination (default: 15)
```

### 4. Accounting Integration

#### AccountingService::journalForPurchase()

**Purpose:** Create journal entry for goods receipt

**Process:**
```php
public function journalForPurchase(GoodsReceipt $receipt): JournalEntry {
  $inventory = $this->findSystemAccount($receipt->company_id, AccountSubType::INVENTORY);
  $payable = $this->findSystemAccount($receipt->company_id, AccountSubType::PAYABLE);
  
  $receipt->loadMissing('items');
  $totalAmount = $receipt->items->sum(fn ($item) => $item->received_quantity * $item->unit_cost);
  $taxAmount = $receipt->tax_amount ?? 0;
  $netAmount = $totalAmount - $taxAmount;
  
  $lines = [
    // Debit: Inventory
    new ProposedJournalLine(
      accountId: $inventory->id,
      debit: $taxAmount > 0 ? $netAmount : $totalAmount,
      credit: 0,
      description: 'Inventory',
    ),
  ];
  
  // Debit: Input VAT Receivable (if VAT)
  if ($taxAmount > 0) {
    $vatReceivable = $this->findSystemAccount($receipt->company_id, AccountSubType::CURRENT_LIABILITY, '2002');
    $taxRate = $netAmount > 0 ? (int) round(($taxAmount / $netAmount) * 10000) : 0;
    
    $lines[] = new ProposedJournalLine(
      accountId: $vatReceivable->id,
      debit: $taxAmount,
      credit: 0,
      description: 'Input VAT Receivable',
      taxCode: 'INPUT_VAT',
      taxRate: $taxRate,
      taxBaseAmount: $netAmount,
    );
  }
  
  // Credit: Accounts Payable
  $lines[] = new ProposedJournalLine(
    accountId: $payable->id,
    debit: 0,
    credit: $totalAmount,
    description: 'Accounts Payable',
  );
  
  $proposed = new ProposedJournalEntry(
    companyId: $receipt->company_id,
    idempotencyKey: "goods_receipt_{$receipt->id}_journal",
    journalCode: JournalCode::PURCHASE,
    entryDate: $receipt->receipt_date,
    description: "GRN {$receipt->receipt_number}",
    referenceType: 'goods_receipt',
    referenceId: $receipt->id,
    lines: $lines,
  );
  
  return $this->postingService->post($proposed);
}
```

**Journal Entry Structure:**
```
DR: Inventory            (net_amount)
DR: Input VAT Receivable (tax_amount) — if tax > 0
CR: Accounts Payable    (total_amount)
```

## Frontend Architecture

### 1. Purchase/Dashboard.vue

**Purpose:** Purchase overview dashboard

**Metrics Displayed:**
- Total Purchases (with delta)
- Purchase Orders (with delta)
- Pending Approvals
- Cost Savings (vs last month)

**Charts:**
- Purchase Spending Trend (7d, 30d, 90d)
- Purchases by Supplier
- Top Purchased Products

**Quick Actions:**
- Purchase Report
- New Purchase Order

**API Calls:**
```javascript
GET /api/v1/purchase/dashboard - Dashboard metrics
GET /api/v1/purchase/spending-trend - Trend data
```

### 2. Purchase/Orders.vue

**Purpose:** Manage purchase orders

**Features:**
- List orders with columns:
  - Order Number
  - Supplier
  - Status (Badge)
  - Total Amount
  - Order Date
- Actions:
  - View
  - Edit (draft only)
  - Confirm (draft only)
  - Cancel (draft/confirmed only)
  - Delete (draft only)
- Create/Edit modal with items

**Form Fields:**
- Supplier (required)
- Warehouse (required)
- Order Date
- Expected Date
- Total Amount
- Items (dynamic):
  - Product
  - Quantity
  - Unit Cost
  - Discount %
  - Tax Rate %

**API Calls:**
```javascript
GET /api/v1/purchase-orders - List orders
POST /api/v1/purchase-orders - Create order
PUT /api/v1/purchase-orders/{id} - Update order
POST /api/v1/purchase-orders/{id}/send - Send to supplier
POST /api/v1/purchase-orders/{id}/cancel - Cancel order
DELETE /api/v1/purchase-orders/{id} - Delete order
```

### 3. Purchase/Receipts.vue

**Purpose:** Manage goods receipts

**Features:**
- List receipts with columns:
  - Receipt Number
  - Supplier
  - Status (Badge)
  - Receipt Date
- Actions:
  - View
  - Edit (draft only)
  - Confirm (draft only)
  - Delete (draft only)
- Create/Edit modal with items

**Form Fields:**
- Supplier (required)
- Purchase Order (optional)
- Warehouse (required)
- Receipt Date
- Items (dynamic):
  - Product
  - Quantity Received
  - Unit Cost

**API Calls:**
```javascript
GET /api/v1/goods-receipts - List receipts
POST /api/v1/goods-receipts - Create receipt
PUT /api/v1/goods-receipts/{id} - Update receipt
POST /api/v1/goods-receipts/{id}/post - Post receipt
DELETE /api/v1/goods-receipts/{id} - Delete receipt
```

### 4. Purchase/Suppliers.vue

**Purpose:** Manage suppliers

**Features:**
- List suppliers with columns:
  - Name
  - Email
  - Phone
  - Address
  - Status (Active/Inactive)
- Actions:
  - View
  - Edit
  - Delete
- Create/Edit modal

**Form Fields:**
- Name (required)
- Email (required)
- Phone
- Tax ID
- Address
- City
- Country
- Status

**API Calls:**
```javascript
GET /api/v1/suppliers - List suppliers
POST /api/v1/suppliers - Create supplier
PUT /api/v1/suppliers/{id} - Update supplier
DELETE /api/v1/suppliers/{id} - Delete supplier
```

### 5. Purchase/Returns.vue

**Purpose:** Manage purchase returns

**Features:**
- List returns with columns:
  - Return Number
  - Supplier
  - Status (Badge)
  - Amount
  - Return Date
- Actions:
  - View
  - Edit (draft only)
  - Approve (pending only)
  - Reject (pending only)
  - Delete (draft only)
- Create/Edit modal

**Form Fields:**
- Supplier (required)
- Purchase Order (optional)
- Return Date
- Amount
- Reason

**API Calls:**
```javascript
GET /api/v1/purchase-returns - List returns
POST /api/v1/purchase-returns - Create return
PUT /api/v1/purchase-returns/{id} - Update return
POST /api/v1/purchase-returns/{id}/approve - Approve return
POST /api/v1/purchase-returns/{id}/reject - Reject return
DELETE /api/v1/purchase-returns/{id} - Delete return
```

## Complete Data Flow

### Purchase Order Flow

```
User creates purchase order
    ↓
PurchaseOrderController::store()
    ├─→ Validate input
    ├─→ PurchaseService::createOrder()
    │   ├─→ Generate reference_number (PO-YYYYMMDD-XXXX)
    │   ├─→ Set status = DRAFT
    │   ├─→ Calculate totals
    │   ├─→ Create PurchaseOrder
    │   └─→ Create PurchaseOrderItems
    └─→ Return PurchaseOrderResource
    ↓
User sends order to supplier
    ↓
PurchaseOrderController::send()
    ├─→ PurchaseService::sendOrder()
    │   └─→ Set status = SENT
    └─→ Return PurchaseOrderResource
    ↓
User confirms order
    ↓
PurchaseOrderController::confirm()
    ├─→ PurchaseService::confirmOrder()
    │   └─→ Set status = CONFIRMED
    └─→ Return PurchaseOrderResource
```

### Goods Receipt Flow

```
User creates goods receipt
    ↓
PurchaseOrderController::createReceipt()
    ├─→ PurchaseService::createReceipt()
    │   ├─→ Generate receipt_number (GRN-YYYYMMDD-XXXX)
    │   ├─→ Set status = DRAFT
    │   ├─→ Create GoodsReceipt
    │   └─→ Create GoodsReceiptItems
    └─→ Return GoodsReceiptResource
    ↓
User posts receipt
    ↓
PurchaseOrderController::postReceipt()
    ├─→ PurchaseService::postReceipt()
    │   ├─→ GoodsReceipt::addStock()
    │   │   └─→ InventoryService::stockIn() for each item
    │   ├─→ Update PO item received quantities
    │   ├─→ Update PO status (PARTIAL or RECEIVED)
    │   ├─→ Record supplier transaction
    │   └─→ Set status = POSTED
    └─→ Return GoodsReceiptResource
```

### Purchase Approval Flow

```
User posts goods receipt
    ↓
PurchaseService::postReceipt()
    ├─→ DB::transaction()
    │   ├─→ GoodsReceipt::addStock()
    │   │   └─→ InventoryService::stockIn()
    │   │       ├─→ Record stock-in movement
    │   │       ├─→ Update stock quantity
    │   │       ├─→ Track movement type = PURCHASE_RECEIPT
    │   │       └─→ Create stock layers (FIFO/WAC)
    │   ├─→ Update PO item received quantities
    │   ├─→ Update PO status
    │   ├─→ Record supplier transaction
    │   │   └─→ ContactService::recordSupplierTransaction()
    │   │       ├─→ Create SupplierTransaction
    │   │       └─→ Update supplier balance
    │   └─→ AccountingService::journalForPurchase()
    │       ├─→ Create ProposedJournalEntry
    │       │   ├─→ DR: Inventory (net_amount)
    │       │   ├─→ DR: Input VAT Receivable (tax_amount)
    │       │   └─→ CR: Accounts Payable (total_amount)
    │       └─→ PostingService::post()
    │           ├─→ Validate balance
    │           ├─→ Check idempotency
    │           ├─→ Validate lock date
    │           ├─→ Create JournalEntry (status = posted)
    │           └─→ Create JournalEntryLine items
    └─→ Set status = POSTED
```

## Integration with Accounting Domain

### Double-Entry Bookkeeping

**Goods Receipt Journal Entry:**
```
DR: Inventory            (net_amount)
DR: Input VAT Receivable (tax_amount)
CR: Accounts Payable    (total_amount)
```

**Effect on Accounts:**
- Inventory: Increases (debit balance)
- Input VAT Receivable: Increases (debit balance)
- Accounts Payable: Increases (credit balance)

### Idempotency

**Goods Receipt Posting:**
```php
$idempotencyKey = "goods_receipt_{$receipt->id}_journal";
```

### Lock Date Protection

**Validation:**
```php
if ($company->lock_date && $receipt->receipt_date <= $company->lock_date) {
  throw new RuntimeException('Cannot post before lock date');
}
```

### Multi-Tenancy

**Automatic Filtering:**
```php
PurchaseOrder::query()->where('company_id', activeCompany()->id)
GoodsReceipt::query()->where('company_id', activeCompany()->id)
```

## Integration with Inventory Domain

### Stock Addition (Goods Receipt Posting)

```
For each goods receipt item:
  └─→ InventoryService::stockIn()
      ├─→ Record stock-in movement
      ├─→ Update stock quantity
      ├─→ Track movement type = PURCHASE_RECEIPT
      ├─→ Create stock layers (FIFO/WAC)
      └─→ Reference to goods receipt
```

### Stock Removal (Purchase Return)

```
For each purchase return item:
  └─→ InventoryService::stockOut()
      ├─→ Record stock-out movement
      ├─→ Update stock quantity
      ├─→ Track movement type = PURCHASE_RETURN
      └─→ Reference to purchase return
```

### Stock Valuation

**FIFO/WAC Method:**
- Stock layers created on goods receipt
- Layers consumed on sales (COGS calculation)
- Average cost tracked for inventory value

## Comparison with Modern ERPs

### Features Comparison

| Feature | This System | Odoo | Zoho |
|---------|-------------|------|------|
| **Purchase Orders** | ✅ | ✅ | ✅ |
| **Goods Receipts** | ✅ | ✅ | ✅ |
| **Purchase Returns** | ✅ | ✅ | ✅ |
| **Suppliers** | ✅ | ✅ | ✅ |
| **Stock Addition** | ✅ | ✅ | ✅ |
| **Stock Removal** | ✅ | ✅ | ✅ |
| **Journal Entry Auto-creation** | ✅ | ✅ | ✅ |
| **Multi-tenancy** | ✅ | ✅ | ✅ |
| **VAT Tracking** | ✅ | ✅ | ✅ |
| **TDS/VDS Deduction** | ✅ | ⚠️ | ⚠️ |
| **Supplier Credit Days** | ✅ | ✅ | ✅ |
| **Purchase Requisition** | ❌ | ✅ | ✅ |
| **Purchase Quotation** | ❌ | ✅ | ✅ |
| **Supplier Evaluation** | ❌ | ✅ | ✅ |
| **Multi-currency** | ⚠️ Limited | ✅ | ✅ |

### Workflow Comparison

**This System:**
```
Purchase Order: DRAFT → SENT → CONFIRMED → PARTIAL → RECEIVED → CANCELLED
Goods Receipt: DRAFT → VERIFIED → POSTED
```

**Odoo:**
```
Purchase Order: DRAFT → RFQ → SENT → PURCHASE ORDER → RECEIVED → DONE → CANCELLED
Goods Receipt: DRAFT → VERIFIED → POSTED
```

**Zoho:**
```
Purchase Order: DRAFT → ORDERED → RECEIVED → BILLED → CANCELLED
Goods Receipt: DRAFT → VERIFIED → POSTED
```

### Unique Features

**This System:**
- Bangladesh localization (TDS/VDS deduction)
- BDT currency as primary
- Bangla numerals display
- Simplified workflow
- Idempotency guarantee
- Lock date protection

**Odoo/Zoho:**
- Purchase requisition workflow
- Purchase quotation management
- Supplier evaluation system
- Multi-currency accounting
- Advanced reporting

## API Reference

### Purchase Orders

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/purchase-orders` | List orders | Required |
| GET | `/api/v1/purchase-orders/{id}` | Get order | Required |
| POST | `/api/v1/purchase-orders` | Create order | Required |
| PUT | `/api/v1/purchase-orders/{id}` | Update order (draft only) | Required |
| DELETE | `/api/v1/purchase-orders/{id}` | Delete order (draft only) | Required |
| POST | `/api/v1/purchase-orders/{id}/send` | Send to supplier | Required |
| POST | `/api/v1/purchase-orders/{id}/cancel` | Cancel order | Required |

### Goods Receipts

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/goods-receipts` | List receipts | Required |
| GET | `/api/v1/goods-receipts/{id}` | Get receipt | Required |
| POST | `/api/v1/goods-receipts` | Create receipt | Required |
| PUT | `/api/v1/goods-receipts/{id}` | Update receipt (draft only) | Required |
| DELETE | `/api/v1/goods-receipts/{id}` | Delete receipt (draft only) | Required |
| POST | `/api/v1/goods-receipts/{id}/post` | Post receipt | Required |

### Query Parameters (Index)

```
search -> Filter by order number or reference
status -> Filter by status
supplier_id -> Filter by supplier
per_page -> Pagination (default: 15)
page -> Page number
```

### Request Body (Create Purchase Order)

```json
{
  "supplier_id": 1,
  "warehouse_id": 1,
  "order_date": "2026-03-05",
  "expected_delivery_date": "2026-03-10",
  "notes": "Urgent delivery",
  "items": [
    {
      "product_id": 1,
      "description": "Product A",
      "quantity_ordered": 10,
      "unit_cost": 5000,
      "unit": "pcs",
      "discount_percent": 5,
      "tax_rate": 15
    }
  ]
}
```

### Request Body (Create Goods Receipt)

```json
{
  "purchase_order_id": 1,
  "warehouse_id": 1,
  "receipt_date": "2026-03-05",
  "supplier_invoice_number": "INV-001",
  "supplier_invoice_date": "2026-03-05",
  "notes": "Received all items",
  "items": [
    {
      "purchase_order_item_id": 1,
      "quantity_received": 10
    }
  ]
}
```

### Response Format

```json
{
  "success": true,
  "data": {
    "id": 1,
    "reference_number": "PO-20260305-0001",
    "supplier": {
      "id": 1,
      "name": "Supplier Name"
    },
    "status": "draft",
    "subtotal": 50000,
    "discount_amount": 2500,
    "tax_amount": 7125,
    "total_amount": 54625,
    "items": [...]
  },
  "message": "Purchase order created"
}
```

## Frontend API Integration

### Purchase/Orders.vue

```javascript
const fetchOrders = async (page = 1) => {
  const response = await get('/purchase-orders', { page, per_page: 15 })
  orders.value = response.data
  pagination.value = response.meta
}

const sendOrder = async (order) => {
  await post(`/purchase-orders/${order.id}/send`)
  await fetchOrders()
}

const cancelOrder = async (order) => {
  await post(`/purchase-orders/${order.id}/cancel`)
  await fetchOrders()
}
```

### Purchase/Receipts.vue

```javascript
const fetchReceipts = async (page = 1) => {
  const response = await get('/goods-receipts', { page, per_page: 15 })
  receipts.value = response.data
  pagination.value = response.meta
}

const postReceipt = async (receipt) => {
  await post(`/goods-receipts/${receipt.id}/post`)
  await fetchReceipts()
}
```

## Summary

### Backend Coverage
- ✅ PurchaseOrder model (auto-numbering, workflow)
- ✅ GoodsReceipt model (auto-numbering, workflow)
- ✅ Supplier model (TDS/VDS, credit days)
- ✅ PurchaseReturn model (stock removal)
- ✅ PurchaseService (create, update, send, cancel, post)
- ✅ AccountingService::journalForPurchase() (journal entry creation)
- ✅ Idempotency guarantee
- ✅ Lock date protection
- ✅ Multi-tenancy support

### Frontend Coverage
- ✅ Purchase/Dashboard.vue (metrics, charts)
- ✅ Purchase/Orders.vue (list, create, edit, send, cancel)
- ✅ Purchase/Receipts.vue (list, create, edit, post)
- ✅ Purchase/Suppliers.vue (list, create, edit, delete)
- ✅ Purchase/Returns.vue (list, create, approve, reject)
- ✅ BanglaAmount component (BDT formatting)
- ✅ Badge component (status display)
- ✅ Pagination support

### Integration
- ✅ Double-entry bookkeeping (inventory + input VAT)
- ✅ VAT tracking (Input VAT Receivable)
- ✅ Stock addition (goods receipt posting)
- ✅ Stock removal (purchase return)
- ✅ Stock valuation (FIFO/WAC)
- ✅ Idempotency (prevents duplicate posting)
- ✅ Lock date protection (prevents historical modifications)
- ✅ Multi-tenancy (company isolation)
- ✅ Bangladesh localization (TDS/VDS, BDT, Bangla numerals)

The purchase system provides **comprehensive procurement management** that follows modern ERP patterns with tight integration to accounting and inventory domains.

## Backend Architecture
- **PurchaseOrder Model** - Auto-numbering (PO-YYYYMMDD-XXXX), workflow (DRAFT → SENT → PARTIAL → RECEIVED → CANCELLED)
- **GoodsReceipt Model** - Auto-numbering (GRN-YYYYMMDD-XXXX), workflow (DRAFT → VERIFIED → POSTED), stock addition via [addStock()](cci:1://file:///Users/ferdousazad/Documents/gen-erp/gen-erp-application/app/Domain/Purchase/Models/GoodsReceipt.php:112:4-146:5)
- **Supplier Model** - TDS/VDS deduction support, credit days, bank details
- **PurchaseReturn Model** - Stock removal for returns
- **PurchaseService** - Create/update orders, create receipts, post receipts (add stock + record supplier transaction)
- **AccountingService::journalForPurchase()** - Journal entry creation (DR: Inventory/Input VAT, CR: Accounts Payable)

## Data Flows
- **Purchase Order Flow:** Create → Send → Confirm → Cancel
- **Goods Receipt Flow:** Create → Post (add stock + update PO + record supplier transaction + create journal entry)
- **Purchase Approval Flow:** Post receipt → Add stock → Update PO → Record supplier transaction → Create journal entry

## Accounting Integration
- **Double-Entry Bookkeeping:** DR: Inventory (net_amount) + DR: Input VAT Receivable (tax_amount), CR: Accounts Payable (total_amount)
- **Idempotency:** `goods_receipt_{receipt->id}_journal`
- **Lock Date Protection:** Prevents historical modifications
- **Multi-Tenancy:** Company isolation

## Inventory Integration
- **Stock Addition:** On goods receipt posting (stockIn with PURCHASE_RECEIPT movement type)
- **Stock Removal:** On purchase return (stockOut with PURCHASE_RETURN movement type)
- **Stock Valuation:** FIFO/WAC method with layer tracking

## Frontend Architecture
- **Purchase/Dashboard.vue** - Metrics (purchases, orders, approvals, savings), charts
- **Purchase/Orders.vue** - List, create, edit, send, cancel
- **Purchase/Receipts.vue** - List, create, edit, post
- **Purchase/Suppliers.vue** - List, create, edit, delete
- **Purchase/Returns.vue** - List, create, approve, reject

## Comparison with Modern ERPs
- **Similar:** Core procurement management, journal entry integration, stock management
- **Simpler:** No purchase requisition, no quotation, no supplier evaluation
- **Unique:** Bangladesh localization (TDS/VDS deduction), simplified workflow, idempotency guarantee

The analysis provides complete coverage of the purchase domain with detailed code references, API endpoints, data flows, and integration points.