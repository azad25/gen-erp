# Sales Domain - Complete Analysis

## Overview

The sales domain manages the complete sales lifecycle from quote to payment, with tight integration to accounting for double-entry bookkeeping and inventory for stock management.

## Backend Architecture

### 1. Core Models

#### SalesOrder Model (`app/Domain/SalesOrder/Models/SalesOrder.php`)

**Purpose:** Starting point of the sales flow

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'branch_id',               // Branch dimension
  'customer_id',             // Customer (FK)
  'warehouse_id',            // Warehouse (FK)
  'reference_number',        // Auto-generated: SO-YYYYMMDD-XXXX
  'customer_reference',      // Customer PO number
  'order_date',              // Order date
  'delivery_date',           // Expected delivery date
  'status',                  // DRAFT, CONFIRMED, CANCELLED
  'subtotal',                // Subtotal in paise
  'discount_amount',         // Discount in paise
  'tax_amount',              // VAT in paise
  'shipping_amount',         // Shipping in paise
  'total_amount',            // Total in paise
  'notes',                  // Notes
  'terms_conditions',        // Terms
  'custom_fields',           // Custom data (JSON)
  'created_by',              // Creator user (FK)
];

// Auto-generates reference_number on creation
$reference_number = "SO-" . now()->format('Ymd') . "-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
```

**Relationships:**
```php
customer() -> Customer
warehouse() -> Warehouse
items() -> SalesOrderItem (hasMany)
invoice() -> Invoice (hasOne)
```

**Workflow States:**
- `DRAFT` - Initial state, editable
- `CONFIRMED` - Stock reserved, ready to convert to invoice
- `CANCELLED` - Order cancelled, stock released

#### Invoice Model (`app/Domain/Invoice/Models/Invoice.php`)

**Purpose:** Bill to customer, may originate from sales order

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'branch_id',               // Branch dimension
  'sales_order_id',          // Source sales order (FK)
  'customer_id',             // Customer (FK)
  'warehouse_id',            // Warehouse (FK)
  'invoice_number',          // Auto-generated: INV-YYYYMMDD-XXXX
  'mushak_number',           // VAT invoice number (BD)
  'invoice_date',            // Invoice date
  'due_date',                // Payment due date
  'status',                  // DRAFT, SENT, OVERDUE, PARTIAL, PAID, CANCELLED
  'subtotal',                // Subtotal in paise
  'discount_amount',         // Discount in paise
  'tax_amount',              // VAT in paise
  'shipping_amount',         // Shipping in paise
  'total_amount',            // Total in paise
  'amount_paid',             // Amount paid in paise
  'notes',                  // Notes
  'terms_conditions',        // Terms
  'custom_fields',           // Custom data (JSON)
  'stock_deducted',          // Stock deducted flag
  'created_by',              // Creator user (FK)
];

// Auto-generates invoice_number on creation
$invoice_number = 'INV-' . now()->format('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
```

**Relationships:**
```php
salesOrder() -> SalesOrder
customer() -> Customer
warehouse() -> Warehouse
items() -> InvoiceItem (hasMany)
payments() -> Payment (hasMany)
```

**Workflow States:**
- `DRAFT` - Initial state, editable
- `SENT` - Sent to customer, stock deducted, journal entry posted
- `OVERDUE` - Past due date
- `PARTIAL` - Partially paid
- `PAID` - Fully paid
- `CANCELLED` - Cancelled with reversal

### 2. Services

#### SalesService (`app/Domain/Sales/Services/SalesService.php`)

**Purpose:** Orchestrates sales operations

**Methods:**

```php
// Create a new sales order
public function createOrder(Company $company, array $data, array $items): SalesOrder {
  // 1. Set company_id
  // 2. Generate reference_number
  // 3. Set status = DRAFT
  // 4. Calculate totals (subtotal, discount, tax, total)
  // 5. Create SalesOrder
  // 6. Create SalesOrderItems with line totals
  return $order;
}

// Confirm order and reserve stock
public function confirmOrder(SalesOrder $order): void {
  // 1. For each item with track_inventory:
  //    - Reserve stock in warehouse
  // 2. Set status = CONFIRMED
}

// Cancel order and release reservations
public function cancelOrder(SalesOrder $order): void {
  // 1. For each item with track_inventory:
  //    - Release reservation
  // 2. Set status = CANCELLED
}

// Convert sales order to invoice
public function convertToInvoice(SalesOrder $order): Invoice {
  // 1. Create Invoice from SalesOrder data
  // 2. Copy items with recalculated totals
  // 3. Set status = DRAFT
  return $invoice;
}

// Create direct invoice (no sales order)
public function createInvoice(Company $company, array $data, array $items): Invoice {
  // 1. Set company_id
  // 2. Generate invoice_number
  // 3. Set status = DRAFT
  // 4. Calculate totals
  // 5. Create Invoice
  // 6. Create InvoiceItems
  return $invoice;
}

// Send invoice - deduct stock and record transaction
public function sendInvoice(Invoice $invoice): void {
  // 1. Call ApproveInvoice action
  // 2. Deduct stock
  // 3. Compute COGS
  // 4. Post journal entry
  // 5. Set status = SENT
}
```

**Helper Method:**
```php
private function calculateTotals(array $items, bool $vatRegistered): array {
  $subtotal = 0;
  $totalDiscount = 0;
  $totalTax = 0;

  foreach ($items as $item) {
    $lineTotal = $item['unit_price'] * $item['quantity'];
    $lineDiscount = (int) round($lineTotal * (($item['discount_percent'] ?? 0) / 100));
    $netAmount = $lineTotal - $lineDiscount;
    $lineTax = $vatRegistered ? (int) round($netAmount * (($item['tax_rate'] ?? 0) / 100)) : 0;
    $finalTotal = $netAmount + $lineTax;

    $subtotal += $lineTotal;
    $totalDiscount += $lineDiscount;
    $totalTax += $lineTax;
  }

  return [
    'subtotal' => $subtotal,
    'discount_amount' => $totalDiscount,
    'tax_amount' => $totalTax,
    'total_amount' => $subtotal - $totalDiscount + $totalTax,
  ];
}
```

### 3. Actions

#### ApproveInvoice (`app/Domain/Sales/Actions/ApproveInvoice.php`)

**Purpose:** Atomically approve invoice - deduct stock, compute COGS, post journal entry

**Process:**
```php
public function execute(Invoice $invoice, ?int $approvedBy = null): Invoice {
  // 1. Lock invoice row for update
  // 2. Double-check status is DRAFT
  // 3. Deduct stock and compute COGS
  // 4. Build and post journal entry (revenue + COGS)
  // 5. Set status = SENT, stock_deducted = true
  return $invoice;
}
```

**Step 1: Deduct Stock and Compute COGS**
```php
private function deductStockAndComputeCogs(Invoice $invoice): int {
  $totalCogs = 0;

  foreach ($invoice->items as $item) {
    if (!$item->product_id) continue;

    $product = Product::find($item->product_id);
    if (!$product->track_inventory) continue;

    // 1. Record stock-out movement
    $movement = $this->inventoryService->stockOut(
      warehouseId: $invoice->warehouse_id,
      productId: $item->product_id,
      quantity: $item->quantity,
      type: StockMovementType::SALE,
      notes: "Invoice {$invoice->invoice_number}",
    );

    // 2. Consume stock layers (FIFO/WAC) to compute COGS
    $cogs = $this->valuationService->consume($movement);
    $totalCogs += $cogs;
  }

  return $totalCogs;
}
```

**Step 2: Build and Post Journal Entry**
```php
private function buildProposedJournal(
  Invoice $invoice,
  string $idempotencyKey,
  ?int $createdBy,
  int $totalCogs = 0,
): ProposedJournalEntry {
  // Revenue lines:
  //   DR: Accounts Receivable  (total_amount)
  //   CR: Sales Revenue        (subtotal)
  //   CR: Output VAT Payable   (tax_amount) — if tax > 0

  // COGS lines (if tracked inventory):
  //   DR: Cost of Goods Sold   (totalCogs)
  //   CR: Inventory            (totalCogs)

  $lines = [
    // Debit: Accounts Receivable
    new ProposedJournalLine(
      accountId: $receivable->id,
      debit: $invoice->total_amount,
      credit: 0,
      description: 'Accounts Receivable',
    ),
    // Credit: Sales Revenue
    new ProposedJournalLine(
      accountId: $revenue->id,
      debit: 0,
      credit: $invoice->subtotal,
      description: 'Sales Revenue',
    ),
  ];

  // VAT separation (if tax > 0)
  if ($invoice->tax_amount > 0) {
    $lines[] = new ProposedJournalLine(
      accountId: $vatPayable->id,
      debit: 0,
      credit: $invoice->tax_amount,
      description: 'Output VAT Payable',
      taxCode: 'OUTPUT_VAT',
      taxRate: $this->computeEffectiveTaxRate($invoice),
      taxBaseAmount: $invoice->subtotal,
    );
  }

  // COGS lines (if tracked inventory)
  if ($totalCogs > 0) {
    $lines[] = new ProposedJournalLine(
      accountId: $cogsAccount->id,
      debit: $totalCogs,
      credit: 0,
      description: 'Cost of Goods Sold',
    );
    $lines[] = new ProposedJournalLine(
      accountId: $inventoryAccount->id,
      debit: 0,
      credit: $totalCogs,
      description: 'Inventory',
    );
  }

  return new ProposedJournalEntry(
    companyId: $invoice->company_id,
    idempotencyKey: $idempotencyKey,
    journalCode: JournalCode::SALES,
    entryDate: $invoice->invoice_date,
    description: "Invoice {$invoice->invoice_number}",
    referenceType: 'invoice',
    referenceId: $invoice->id,
    lines: $lines,
  );
}
```

**Journal Entry Structure:**
```
DR: Accounts Receivable  (total_amount)
CR: Sales Revenue        (subtotal)
CR: Output VAT Payable   (tax_amount)
DR: Cost of Goods Sold   (totalCogs)
CR: Inventory            (totalCogs)
```

#### CancelInvoiceWithReversal (`app/Domain/Sales/Actions/CancelInvoiceWithReversal.php`)

**Purpose:** Cancel posted invoice - restore stock, reverse journal entry

**Process:**
```php
public function execute(Invoice $invoice, ?int $cancelledBy = null, ?string $reason = null): Invoice {
  // 1. Lock invoice row for update
  // 2. Double-check status is SENT or OVERDUE
  // 3. Reverse journal entry
  // 4. Restore stock
  // 5. Set status = CANCELLED, stock_deducted = false
  return $invoice;
}
```

**Step 1: Reverse Journal Entry**
```php
private function reverseJournal(Invoice $invoice, ?int $cancelledBy, ?string $reason): void {
  $originalEntry = JournalEntry::where('reference_type', 'invoice')
    ->where('reference_id', $invoice->id)
    ->where('status', JournalEntryStatus::POSTED)
    ->first();

  if ($originalEntry) {
    $this->postingService->reverse(
      original: $originalEntry,
      idempotencyKey: "invoice_{$invoice->id}_cancel_reversal",
      description: $reason ?? "Cancellation of Invoice {$invoice->invoice_number}",
      reversedBy: $cancelledBy,
    );
  }
}
```

**Step 2: Restore Stock**
```php
private function restoreStock(Invoice $invoice): void {
  foreach ($invoice->items as $item) {
    if (!$item->product_id) continue;

    $product = Product::find($item->product_id);
    if (!$product->track_inventory) continue;

    $this->inventoryService->stockIn(
      warehouseId: $invoice->warehouse_id,
      productId: $item->product_id,
      quantity: $item->quantity,
      type: StockMovementType::SALE_RETURN,
      notes: "Cancelled Invoice {$invoice->invoice_number}",
      reference: $invoice,
    );
  }
}
```

### 4. Controllers

#### SalesOrderController (`app/Http/Controllers/Api/V1/SalesOrderController.php`)

**API Endpoints:**
```php
GET    /api/v1/sales-orders              - List orders with filters
GET    /api/v1/sales-orders/{id}         - Get order details
POST   /api/v1/sales-orders              - Create order
PUT    /api/v1/sales-orders/{id}         - Update order (draft only)
DELETE /api/v1/sales-orders/{id}         - Delete order (draft only)
POST   /api/v1/sales-orders/{id}/confirm - Confirm order
POST   /api/v1/sales-orders/{id}/cancel  - Cancel order
POST   /api/v1/sales-orders/{id}/convert - Convert to invoice
```

**Query Parameters (Index):**
```
search -> Filter by customer name
status -> Filter by status
customer_id -> Filter by customer
per_page -> Pagination (default: 15)
```

#### InvoiceController (`app/Http/Controllers/Api/V1/InvoiceController.php`)

**API Endpoints:**
```php
GET    /api/v1/invoices              - List invoices with filters
GET    /api/v1/invoices/{id}         - Get invoice details
POST   /api/v1/invoices              - Create invoice
PUT    /api/v1/invoices/{id}         - Update invoice (draft only)
DELETE /api/v1/invoices/{id}         - Delete invoice (draft only)
POST   /api/v1/invoices/{id}/send   - Approve and send invoice
POST   /api/v1/invoices/{id}/cancel - Cancel invoice
POST   /api/v1/invoices/{id}/pay    - Record payment
```

## Frontend Architecture

### 1. Sales/Dashboard.vue

**Purpose:** Sales overview dashboard

**Metrics Displayed:**
- Total Revenue (with delta)
- Sales Orders (with delta)
- Outstanding (unpaid invoices)
- Conversion Rate (leads to sales)

**Charts:**
- Sales Revenue Trend (7d, 30d, 90d)
- Revenue by Customer
- Top Selling Products

**Quick Actions:**
- Export Report
- New Sale

**API Calls:**
```javascript
GET /api/v1/sales/dashboard - Dashboard metrics
GET /api/v1/sales/revenue-trend - Trend data
```

### 2. Sales/Orders.vue

**Purpose:** Manage sales orders

**Features:**
- List orders with columns:
  - Order Number
  - Customer
  - Status (Badge)
  - Total Amount
  - Order Date
- Actions:
  - View
  - Edit (draft only)
  - Confirm (draft only)
  - Convert to Invoice (confirmed only)
  - Cancel (draft/confirmed only)
  - Delete (draft only)
- Create/Edit modal with items

**Form Fields:**
- Customer (required)
- Warehouse (required)
- Order Date
- Total Amount
- Items (dynamic):
  - Product
  - Quantity
  - Unit Price
  - Discount %
  - Tax Rate %

**API Calls:**
```javascript
GET /api/v1/sales-orders - List orders
POST /api/v1/sales-orders - Create order
PUT /api/v1/sales-orders/{id} - Update order
POST /api/v1/sales-orders/{id}/confirm - Confirm order
POST /api/v1/sales-orders/{id}/convert - Convert to invoice
POST /api/v1/sales-orders/{id}/cancel - Cancel order
DELETE /api/v1/sales-orders/{id} - Delete order
```

### 3. Sales/Invoices.vue

**Purpose:** Manage invoices

**Features:**
- List invoices with columns:
  - Invoice Number
  - Customer
  - Status (Badge)
  - Total Amount
  - Invoice Date
  - Due Date
- Actions:
  - View
  - Edit (draft only)
  - Send (draft only)
  - Mark Paid (sent/overdue/partial only)
  - Delete (draft only)
- Create/Edit modal with items

**Form Fields:**
- Customer (required)
- Invoice Date
- Due Date
- Total Amount
- Items (dynamic):
  - Product
  - Quantity
  - Unit Price
  - Discount %
  - Tax Rate %

**API Calls:**
```javascript
GET /api/v1/invoices - List invoices
POST /api/v1/invoices - Create invoice
PUT /api/v1/invoices/{id} - Update invoice
POST /api/v1/invoices/{id}/send - Approve and send
POST /api/v1/invoices/{id}/cancel - Cancel invoice
POST /api/v1/invoices/{id}/pay - Record payment
DELETE /api/v1/invoices/{id} - Delete invoice
```

### 4. Sales/Customers.vue

**Purpose:** Manage customers

**Features:**
- List customers with columns:
  - Name
  - Email
  - Phone
  - Credit Limit
  - Balance
- Actions:
  - View
  - Edit
  - Delete
- Create/Edit modal

**API Calls:**
```javascript
GET /api/v1/customers - List customers
POST /api/v1/customers - Create customer
PUT /api/v1/customers/{id} - Update customer
DELETE /api/v1/customers/{id} - Delete customer
```

### 5. Sales/CreditNotes.vue

**Purpose:** Manage credit notes

**Features:**
- List credit notes
- Create credit notes
- Link to original invoice
- Reverse journal entries

**API Calls:**
```javascript
GET /api/v1/credit-notes - List credit notes
POST /api/v1/credit-notes - Create credit note
```

### 6. Sales/Returns.vue

**Purpose:** Manage sales returns

**Features:**
- List returns
- Create return
- Restore stock
- Reverse journal entries

**API Calls:**
```javascript
GET /api/v1/returns - List returns
POST /api/v1/returns - Create return
```

## Complete Data Flow

### Sales Order Flow

```
User creates sales order
    ↓
SalesOrderController::store()
    ├─→ Validate input
    ├─→ SalesService::createOrder()
    │   ├─→ Generate reference_number (SO-YYYYMMDD-XXXX)
    │   ├─→ Set status = DRAFT
    │   ├─→ Calculate totals
    │   ├─→ Create SalesOrder
    │   └─→ Create SalesOrderItems
    └─→ Return SalesOrderResource
    ↓
User confirms order
    ↓
SalesOrderController::confirm()
    ├─→ SalesService::confirmOrder()
    │   ├─→ For each item with track_inventory:
    │   │   └─→ InventoryService::reserve()
    │   └─→ Set status = CONFIRMED
    └─→ Return SalesOrderResource
    ↓
User converts to invoice
    ↓
SalesOrderController::convert()
    ├─→ SalesService::convertToInvoice()
    │   ├─→ Generate invoice_number (INV-YYYYMMDD-XXXX)
    │   ├─→ Set status = DRAFT
    │   ├─→ Create Invoice
    │   └─→ Create InvoiceItems
    └─→ Return InvoiceResource
```

### Invoice Approval Flow

```
User sends invoice
    ↓
InvoiceController::send()
    ├─→ SalesService::sendInvoice()
    │   └─→ ApproveInvoice::execute()
    │       ├─→ Lock invoice row
    │       ├─→ Check status is DRAFT
    │       ├─→ Deduct stock and compute COGS
    │       │   ├─→ For each item with track_inventory:
    │       │   │   ├─→ InventoryService::stockOut()
    │       │   │   └─→ InventoryValuationService::consume()
    │       │   └─→ Return totalCogs
    │       ├─→ Build and post journal entry
    │       │   ├─→ Create ProposedJournalEntry
    │       │   │   ├─→ DR: Accounts Receivable (total_amount)
    │       │   │   ├─→ CR: Sales Revenue (subtotal)
    │       │   │   ├─→ CR: Output VAT Payable (tax_amount)
    │       │   │   ├─→ DR: Cost of Goods Sold (totalCogs)
    │       │   │   └─→ CR: Inventory (totalCogs)
    │       │   └─→ PostingService::post()
    │       │       ├─→ Validate balance
    │       │       ├─→ Check idempotency
    │       │       ├─→ Validate lock date
    │       │       ├─→ Create JournalEntry (status = posted)
    │       │       └─→ Create JournalEntryLine items
    │       └─→ Set status = SENT, stock_deducted = true
    └─→ Return InvoiceResource
```

### Invoice Cancellation Flow

```
User cancels invoice
    ↓
InvoiceController::cancel()
    ├─→ SalesService::cancelInvoice()
    │   └─→ CancelInvoiceWithReversal::execute()
    │       ├─→ Lock invoice row
    │       ├─→ Check status is SENT or OVERDUE
    │       ├─→ Reverse journal entry
    │       │   ├─→ Find original JournalEntry
    │       │   └─→ PostingService::reverse()
    │       │       ├─→ Swap debits ↔ credits
    │       │       ├─→ Create new JournalEntry
    │       │       └─→ Link to original
    │       ├─→ Restore stock
    │       │   └─→ For each item with track_inventory:
    │       │       └─→ InventoryService::stockIn()
    │       └─→ Set status = CANCELLED, stock_deducted = false
    └─→ Return InvoiceResource
```

## Integration with Accounting Domain

### Double-Entry Bookkeeping

**Invoice Approval Journal Entry:**
```
DR: Accounts Receivable  (total_amount)
CR: Sales Revenue        (subtotal)
CR: Output VAT Payable   (tax_amount)
DR: Cost of Goods Sold   (totalCogs)
CR: Inventory            (totalCogs)
```

**Effect on Accounts:**
- Accounts Receivable: Increases (debit balance)
- Sales Revenue: Increases (credit balance)
- Output VAT Payable: Increases (credit balance)
- Cost of Goods Sold: Increases (debit balance)
- Inventory: Decreases (credit balance)

**Invoice Cancellation Reversal:**
```
DR: Sales Revenue        (subtotal)
DR: Output VAT Payable   (tax_amount)
DR: Inventory            (totalCogs)
CR: Accounts Receivable  (total_amount)
CR: Cost of Goods Sold   (totalCogs)
```

### Idempotency

**Invoice Approval:**
```php
$idempotencyKey = "invoice_{$invoice->id}_approve";
```

**Invoice Cancellation:**
```php
$idempotencyKey = "invoice_{$invoice->id}_cancel_reversal";
```

### Lock Date Protection

**Validation:**
```php
if ($company->lock_date && $invoice->invoice_date <= $company->lock_date) {
  throw new RuntimeException('Cannot post before lock date');
}
```

### Multi-Tenancy

**Automatic Filtering:**
```php
SalesOrder::query()->where('company_id', activeCompany()->id)
Invoice::query()->where('company_id', activeCompany()->id)
```

## Integration with Inventory Domain

### Stock Deduction (Invoice Approval)

```
For each invoice item:
  ├─→ InventoryService::stockOut()
  │   ├─→ Record stock-out movement
  │   ├─→ Update stock quantity
  │   └─→ Track movement type = SALE
  └─→ InventoryValuationService::consume()
      ├─→ Consume stock layers (FIFO/WAC)
      ├─→ Compute COGS for this item
      └─→ Return COGS amount
```

### Stock Restoration (Invoice Cancellation)

```
For each invoice item:
  └─→ InventoryService::stockIn()
      ├─→ Record stock-in movement
      ├─→ Update stock quantity
      ├─→ Track movement type = SALE_RETURN
      └─→ Reference to cancelled invoice
```

### Stock Reservation (Order Confirmation)

```
For each order item:
  └─→ InventoryService::reserve()
      ├─→ Reserve stock quantity
      └─→ Prevent other orders from using
```

### Stock Release (Order Cancellation)

```
For each order item:
  └─→ InventoryService::releaseReservation()
      ├─→ Release reserved quantity
      └─→ Make available for other orders
```

## Comparison with Modern ERPs

### Features Comparison

| Feature | This System | Odoo | Zoho |
|---------|-------------|------|------|
| **Sales Orders** | ✅ | ✅ | ✅ |
| **Invoices** | ✅ | ✅ | ✅ |
| **Credit Notes** | ✅ | ✅ | ✅ |
| **Returns** | ✅ | ✅ | ✅ |
| **Stock Reservation** | ✅ | ✅ | ✅ |
| **Stock Deduction** | ✅ | ✅ | ✅ |
| **COGS Calculation** | ✅ | ✅ | ✅ |
| **Journal Entry Auto-creation** | ✅ | ✅ | ✅ |
| **Multi-tenancy** | ✅ | ✅ | ✅ |
| **VAT Tracking** | ✅ | ✅ | ✅ |
| **Mushak Number** | ✅ | ❌ | ❌ |
| **Sales Pipeline** | ⚠️ Basic | ✅ Advanced | ✅ Advanced |
| **Quotations** | ⚠️ Basic | ✅ Advanced | ✅ Advanced |
| **Sales Teams** | ❌ | ✅ | ✅ |
| **Commission Tracking** | ❌ | ✅ | ✅ |
| **Sales Forecast** | ❌ | ✅ | ✅ |
| **Multi-currency** | ⚠️ Limited | ✅ | ✅ |

### Workflow Comparison

**This System:**
```
Sales Order: DRAFT → CONFIRMED → CANCELLED
Invoice: DRAFT → SENT → OVERDUE → PARTIAL → PAID → CANCELLED
```

**Odoo:**
```
Sales Order: DRAFT → QUOTATION → SENT → SALE → DONE → CANCELLED
Invoice: DRAFT → SENT → OVERDUE → PARTIAL → PAID → CANCELLED
```

**Zoho:**
```
Sales Order: DRAFT → CONFIRMED → INVOICED → CANCELLED
Invoice: DRAFT → SENT → OVERDUE → PARTIAL → PAID → CANCELLED
```

### Unique Features

**This System:**
- Bangladesh localization (Mushak number)
- BDT currency as primary
- Bangla numerals display
- Simplified workflow
- Idempotency guarantee
- Lock date protection

**Odoo/Zoho:**
- Advanced sales pipeline
- Sales teams and territories
- Commission tracking
- Sales forecasting
- Multi-currency accounting
- Advanced reporting

## API Reference

### Sales Orders

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/sales-orders` | List orders | Required |
| GET | `/api/v1/sales-orders/{id}` | Get order | Required |
| POST | `/api/v1/sales-orders` | Create order | Required |
| PUT | `/api/v1/sales-orders/{id}` | Update order (draft only) | Required |
| DELETE | `/api/v1/sales-orders/{id}` | Delete order (draft only) | Required |
| POST | `/api/v1/sales-orders/{id}/confirm` | Confirm order | Required |
| POST | `/api/v1/sales-orders/{id}/cancel` | Cancel order | Required |
| POST | `/api/v1/sales-orders/{id}/convert` | Convert to invoice | Required |

### Invoices

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/invoices` | List invoices | Required |
| GET | `/api/v1/invoices/{id}` | Get invoice | Required |
| POST | `/api/v1/invoices` | Create invoice | Required |
| PUT | `/api/v1/invoices/{id}` | Update invoice (draft only) | Required |
| DELETE | `/api/v1/invoices/{id}` | Delete invoice (draft only) | Required |
| POST | `/api/v1/invoices/{id}/send` | Approve and send | Required |
| POST | `/api/v1/invoices/{id}/cancel` | Cancel invoice | Required |
| POST | `/api/v1/invoices/{id}/pay` | Record payment | Required |

### Query Parameters (Index)

```
search -> Filter by customer name
status -> Filter by status
customer_id -> Filter by customer
per_page -> Pagination (default: 15)
page -> Page number
```

### Request Body (Create Sales Order)

```json
{
  "customer_id": 1,
  "warehouse_id": 1,
  "order_date": "2026-03-05",
  "delivery_date": "2026-03-10",
  "notes": "Urgent delivery",
  "items": [
    {
      "product_id": 1,
      "description": "Product A",
      "quantity": 10,
      "unit_price": 5000,
      "unit": "pcs",
      "discount_percent": 5,
      "tax_rate": 15
    }
  ]
}
```

### Request Body (Create Invoice)

```json
{
  "customer_id": 1,
  "warehouse_id": 1,
  "invoice_date": "2026-03-05",
  "due_date": "2026-04-04",
  "notes": "Payment due in 30 days",
  "items": [
    {
      "product_id": 1,
      "description": "Product A",
      "quantity": 10,
      "unit_price": 5000,
      "unit": "pcs",
      "discount_percent": 5,
      "tax_rate": 15
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
    "reference_number": "SO-20260305-0001",
    "customer": {
      "id": 1,
      "name": "Customer Name"
    },
    "status": "draft",
    "subtotal": 50000,
    "discount_amount": 2500,
    "tax_amount": 7125,
    "total_amount": 54625,
    "items": [...]
  },
  "message": "Sales order created"
}
```

## Frontend API Integration

### Sales/Orders.vue

```javascript
const fetchOrders = async (page = 1) => {
  const response = await get('/sales-orders', { page, per_page: 15 })
  orders.value = response.data
  pagination.value = response.meta
}

const confirmOrder = async (order) => {
  await post(`/sales-orders/${order.id}/confirm`)
  await fetchOrders()
}

const convertToInvoice = async (order) => {
  await post(`/sales-orders/${order.id}/convert`)
  await fetchOrders()
}
```

### Sales/Invoices.vue

```javascript
const fetchInvoices = async (page = 1) => {
  const response = await get('/invoices', { page, per_page: 15 })
  invoices.value = response.data
  pagination.value = response.meta
}

const sendInvoice = async (invoice) => {
  await post(`/invoices/${invoice.id}/send`)
  await fetchInvoices()
}

const cancelInvoice = async (invoice) => {
  await post(`/invoices/${invoice.id}/cancel`)
  await fetchInvoices()
}
```

## Summary

### Backend Coverage
- ✅ SalesOrder model (auto-numbering, workflow)
- ✅ Invoice model (auto-numbering, workflow)
- ✅ SalesService (create, confirm, cancel, convert)
- ✅ ApproveInvoice action (stock deduction, COGS, journal entry)
- ✅ CancelInvoiceWithReversal action (stock restoration, journal reversal)
- ✅ SalesOrderController (CRUD API)
- ✅ InvoiceController (CRUD API)
- ✅ Idempotency guarantee
- ✅ Lock date protection
- ✅ Multi-tenancy support

### Frontend Coverage
- ✅ Sales/Dashboard.vue (metrics, charts)
- ✅ Sales/Orders.vue (list, create, edit, confirm, convert, cancel)
- ✅ Sales/Invoices.vue (list, create, edit, send, cancel, pay)
- ✅ Sales/Customers.vue (list, create, edit, delete)
- ✅ Sales/CreditNotes.vue (list, create)
- ✅ Sales/Returns.vue (list, create)
- ✅ BanglaAmount component (BDT formatting)
- ✅ Badge component (status display)
- ✅ Pagination support

### Integration
- ✅ Double-entry bookkeeping (revenue + COGS)
- ✅ VAT tracking (Output VAT Payable)
- ✅ Stock deduction (invoice approval)
- ✅ Stock restoration (invoice cancellation)
- ✅ Stock reservation (order confirmation)
- ✅ COGS calculation (FIFO/WAC)
- ✅ Idempotency (prevents duplicate posting)
- ✅ Lock date protection (prevents historical modifications)
- ✅ Multi-tenancy (company isolation)
- ✅ Bangladesh localization (Mushak number, BDT, Bangla numerals)

The sales system provides **comprehensive sales management** that follows modern ERP patterns with tight integration to accounting and inventory domains.

## Backend Architecture
- **SalesOrder Model** - Auto-numbering (SO-YYYYMMDD-XXXX), workflow (DRAFT → CONFIRMED → CANCELLED)
- **Invoice Model** - Auto-numbering (INV-YYYYMMDD-XXXX), workflow (DRAFT → SENT → OVERDUE → PARTIAL → PAID → CANCELLED)
- **SalesService** - Create/confirm/cancel orders, convert to invoice, create direct invoice
- **ApproveInvoice Action** - Deduct stock, compute COGS, post journal entry (revenue + COGS)
- **CancelInvoiceWithReversal Action** - Restore stock, reverse journal entry
- **Controllers** - SalesOrderController, InvoiceController with full CRUD APIs

## Data Flows
- **Sales Order Flow:** Create → Confirm (reserve stock) → Convert to Invoice
- **Invoice Approval Flow:** Send → Deduct stock → Compute COGS → Post journal entry (DR: Receivable/COGS, CR: Revenue/VAT/Inventory)
- **Invoice Cancellation Flow:** Cancel → Reverse journal entry → Restore stock

## Accounting Integration
- **Double-Entry Bookkeeping:** Revenue recognition + COGS calculation
- **Journal Entry Structure:** DR: Accounts Receivable/COGS, CR: Sales Revenue/VAT/Inventory
- **Idempotency:** Prevents duplicate posting
- **Lock Date Protection:** Prevents historical modifications
- **Multi-Tenancy:** Company isolation

## Frontend Architecture
- **Sales/Dashboard.vue** - Metrics (revenue, orders, outstanding, conversion), charts
- **Sales/Orders.vue** - List, create, edit, confirm, convert, cancel
- **Sales/Invoices.vue** - List, create, edit, send, cancel, pay
- **Sales/Customers.vue** - Customer management
- **Sales/CreditNotes.vue** - Credit note management
- **Sales/Returns.vue** - Sales return management

## Inventory Integration
- **Stock Deduction:** On invoice approval (stockOut + consume layers)
- **Stock Restoration:** On invoice cancellation (stockIn)
- **Stock Reservation:** On order confirmation
- **Stock Release:** On order cancellation

## Comparison with Modern ERPs
- **Similar:** Core sales management, journal entry integration, stock management
- **Simpler:** No sales pipeline, no quotations, no sales teams, no commission tracking
- **Unique:** Bangladesh localization (Mushak number), simplified workflow, idempotency guarantee

The analysis provides complete coverage of the sales domain with detailed code references, API endpoints, data flows, and integration points.
