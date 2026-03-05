# Reports Domain - Complete Analysis

## Overview

The Reports domain provides comprehensive reporting capabilities including financial statements, aging reports, inventory valuation, VAT reports, and a flexible report builder for custom reports. It supports Bangladesh-specific VAT formats (Mushak 6.1, 6.2, 6.6, 9.1) and integrates with all major domains.

## Backend Architecture

### 1. Core Models

#### SavedReport Model (`app/Models/SavedReport.php`)

**Purpose:** Save report configurations for the report builder

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'created_by',              // User who created
  'name',                   // Report name
  'entity_type',            // Entity type (customer, product, invoice, etc.)
  'selected_fields',        // Selected fields (JSON)
  'filters',                // Filters (JSON)
  'group_by',               // Group by field
  'aggregate',             // Aggregations (JSON)
  'sort_field',             // Sort field
  'sort_direction',         // Sort direction (asc/desc)
  'visualisation',          // Visualization type (table, chart, etc.)
  'is_scheduled',           // Is scheduled
  'schedule_frequency',     // Schedule frequency (daily, weekly, monthly)
  'schedule_recipients',    // Email recipients (JSON)
  'last_run_at',            // Last run timestamp
];
```

**Relationships:**
```php
creator() -> User
```

**Entity Types Supported:**
- `customer` - Customer reports
- `product` - Product reports
- `invoice`, `sales` - Sales/Invoice reports
- `purchase`, `purchases` - Purchase reports
- `employee`, `employees` - Employee reports
- `expense`, `expenses` - Expense reports
- `supplier` - Supplier reports

### 2. Services

#### ReportService (`app/Domain/Report/Services/ReportService.php`)

**Purpose:** Basic report generation for financial statements

**Methods:**

```php
public function generateReport(string $reportId, array $filters = []): array {
  $reports = [
    'trial_balance' => [
      'id' => 'trial_balance',
      'name' => 'Trial Balance',
      'type' => 'financial',
      'data' => $this->getTrialBalanceData(),
    ],
    'profit_loss' => [
      'id' => 'profit_loss',
      'name' => 'Profit & Loss Statement',
      'type' => 'financial',
      'data' => $this->getProfitLossData(),
    ],
    'balance_sheet' => [
      'id' => 'balance_sheet',
      'name' => 'Balance Sheet',
      'type' => 'financial',
      'data' => $this->getBalanceSheetData(),
    ],
  ];

  return $reports[$reportId] ?? [];
}
```

#### ReportBuilderService (`app/Services/ReportBuilderService.php`)

**Purpose:** Executes report queries, exports, and scheduling for the report builder

**Methods:**

```php
/**
 * Get available fields for an entity type (including custom fields).
 */
public function getAvailableFields(string $entityType): array {
  $base = match ($entityType) {
    'customer' => [
      ['key' => 'id', 'label' => __('ID')],
      ['key' => 'name', 'label' => __('Name')],
      ['key' => 'email', 'label' => __('Email')],
      ['key' => 'phone', 'label' => __('Phone')],
      ['key' => 'district', 'label' => __('District')],
      ['key' => 'balance', 'label' => __('Balance')],
      ['key' => 'created_at', 'label' => __('Created At')],
    ],
    'product' => [
      ['key' => 'id', 'label' => __('ID')],
      ['key' => 'name', 'label' => __('Name')],
      ['key' => 'sku', 'label' => __('SKU')],
      ['key' => 'selling_price', 'label' => __('Price')],
      ['key' => 'cost_price', 'label' => __('Cost Price')],
      ['key' => 'product_type', 'label' => __('Type')],
      ['key' => 'is_active', 'label' => __('Active')],
      ['key' => 'created_at', 'label' => __('Created At')],
    ],
    'invoice', 'sales' => [
      ['key' => 'id', 'label' => __('ID')],
      ['key' => 'invoice_number', 'label' => __('Invoice Number')],
      ['key' => 'customer_id', 'label' => __('Customer')],
      ['key' => 'total_amount', 'label' => __('Total')],
      ['key' => 'status', 'label' => __('Status')],
      ['key' => 'due_date', 'label' => __('Due Date')],
      ['key' => 'invoice_date', 'label' => __('Invoice Date')],
      ['key' => 'created_at', 'label' => __('Created At')],
    ],
    // ... more entity types
  };

  // Append custom fields
  $customDefs = $this->customFieldService->getDefinitions($entityType);
  foreach ($customDefs as $def) {
    $base[] = ['key' => "cf_{$def->field_key}", 'label' => $def->label];
  }

  return $base;
}

/**
 * Execute a saved report and return results.
 */
public function run(SavedReport $report): array {
  $modelClass = $this->resolveModel($report->entity_type ?? '');

  if (!$modelClass) {
    return ['columns' => [], 'rows' => [], 'total' => 0];
  }

  $query = $modelClass::query();

  // Apply selected fields
  $selectedFields = $report->selected_fields ?? ['*'];
  $dbFields = collect($selectedFields)->filter(fn ($f) => !str_starts_with($f, 'cf_'))->all();

  if (!empty($dbFields) && !in_array('*', $dbFields)) {
    $query->select($dbFields);
  }

  // Apply filters
  if ($report->filters) {
    foreach ($report->filters as $filter) {
      $this->applyFilter($query, $filter);
    }
  }

  // Apply grouping
  if ($report->group_by) {
    $query->groupBy($report->group_by);
  }

  // Apply aggregations
  if ($report->aggregate) {
    foreach ($report->aggregate as $agg) {
      $this->applyAggregation($query, $agg);
    }
  }

  // Apply sorting
  if ($report->sort_field) {
    $query->orderBy($report->sort_field, $report->sort_direction ?? 'asc');
  }

  $results = $query->get()->toArray();

  // Add custom field values
  if ($report->selected_fields) {
    $results = $this->addCustomFieldValues($results, $report->entity_type);
  }

  return [
    'columns' => $report->selected_fields ?? ['*'],
    'rows' => $results,
    'total' => count($results),
  ];
}
```

#### CashFlowReportService (`app/Domain/Report/Services/CashFlowReportService.php`)

**Purpose:** Generates Cash Flow Statement using the indirect method

**Methods:**

```php
public function generateCashFlowStatement(
  Company $company,
  Carbon $fromDate,
  Carbon $toDate
): array {
  // Get net income for the period
  $netIncome = $this->getNetIncome($company, $fromDate, $toDate);

  // Get operating activities
  $operatingActivities = $this->getOperatingActivities($company, $fromDate, $toDate, $netIncome);

  // Get investing activities
  $investingActivities = $this->getInvestingActivities($company, $fromDate, $toDate);

  // Get financing activities
  $financingActivities = $this->getFinancingActivities($company, $fromDate, $toDate);

  // Calculate net change in cash
  $netChangeInCash = $operatingActivities['net_cash_from_operations'] +
                    $investingActivities['net_cash_from_investing'] +
                    $financingActivities['net_cash_from_financing'];

  // Get beginning and ending cash balances
  $cashBeginning = $this->getCashBalance($company, $fromDate->copy()->subDay());
  $cashEnding = $this->getCashBalance($company, $toDate);

  return [
    'period' => $fromDate->format('d M Y') . ' - ' . $toDate->format('d M Y'),
    'company' => $company->name,
    'operating_activities' => $operatingActivities,
    'investing_activities' => $investingActivities,
    'financing_activities' => $financingActivities,
    'net_change_in_cash' => $netChangeInCash,
    'cash_beginning' => $cashBeginning,
    'cash_ending' => $cashEnding,
    'reconciliation_difference' => $cashEnding - ($cashBeginning + $netChangeInCash),
  ];
}
```

**Cash Flow Categories:**
- **Operating Activities:** Net income, depreciation, amortization, bad debt expense, working capital changes (AR, inventory, prepaid expenses, AP, accrued liabilities, deferred revenue)
- **Investing Activities:** Capital expenditures, asset sales, investments
- **Financing Activities:** Loans, equity, dividends

#### AgingReportService (`app/Domain/Report/Services/AgingReportService.php`)

**Purpose:** Generates Accounts Receivable and Accounts Payable aging reports

**Methods:**

```php
/**
 * Generate Accounts Receivable aging report.
 */
public function accountsReceivableAging(Company $company, ?Carbon $asOfDate = null): array {
  $asOfDate = $asOfDate ?? now();

  // Get all unpaid/partially paid invoices
  $invoices = Invoice::where('company_id', $company->id)
    ->whereIn('status', ['sent', 'partial'])
    ->where('invoice_date', '<=', $asOfDate)
    ->where('balance_due', '>', 0)
    ->with(['customer'])
    ->get();

  $customerAging = $invoices->groupBy('customer_id')->map(function ($customerInvoices, $customerId) use ($asOfDate) {
    $customer = $customerInvoices->first()->customer;
    $aging = $this->calculateAgingBuckets($customerInvoices, $asOfDate, 'invoice_date', 'balance_due');

    return [
      'customer_id' => $customerId,
      'customer_name' => $customer->name ?? 'Unknown Customer',
      'customer_code' => $customer->customer_code ?? '',
      'total_outstanding' => $aging['total'],
      'current' => $aging['current'],
      'days_1_30' => $aging['days_1_30'],
      'days_31_60' => $aging['days_31_60'],
      'days_61_90' => $aging['days_61_90'],
      'days_over_90' => $aging['days_over_90'],
      'invoice_count' => $customerInvoices->count(),
      'oldest_invoice_date' => $customerInvoices->min('invoice_date'),
      'largest_invoice_amount' => $customerInvoices->max('balance_due'),
    ];
  })->sortByDesc('total_outstanding');

  // Calculate summary totals
  $summary = [
    'total_outstanding' => $customerAging->sum('total_outstanding'),
    'current' => $customerAging->sum('current'),
    'days_1_30' => $customerAging->sum('days_1_30'),
    'days_31_60' => $customerAging->sum('days_31_60'),
    'days_61_90' => $customerAging->sum('days_61_90'),
    'days_over_90' => $customerAging->sum('days_over_90'),
    'customer_count' => $customerAging->count(),
  ];

  return [
    'as_of_date' => $asOfDate->format('d M Y'),
    'company' => $company->name,
    'customers' => $customerAging->values()->toArray(),
    'summary' => $summary,
  ];
}

/**
 * Generate Accounts Payable aging report.
 */
public function accountsPayableAging(Company $company, ?Carbon $asOfDate = null): array {
  $asOfDate = $asOfDate ?? now();

  // Get all unpaid/partially paid purchase orders
  $purchaseOrders = PurchaseOrder::where('company_id', $company->id)
    ->whereIn('status', ['sent', 'partial'])
    ->where('po_date', '<=', $asOfDate)
    ->where('balance_due', '>', 0)
    ->with(['supplier'])
    ->get();

  $supplierAging = $purchaseOrders->groupBy('supplier_id')->map(function ($supplierPOs, $supplierId) use ($asOfDate) {
    $supplier = $supplierPOs->first()->supplier;
    $aging = $this->calculateAgingBuckets($supplierPOs, $asOfDate, 'po_date', 'balance_due');

    return [
      'supplier_id' => $supplierId,
      'supplier_name' => $supplier->name ?? 'Unknown Supplier',
      'supplier_code' => $supplier->supplier_code ?? '',
      'total_outstanding' => $aging['total'],
      'current' => $aging['current'],
      'days_1_30' => $aging['days_1_30'],
      'days_31_60' => $aging['days_31_60'],
      'days_61_90' => $aging['days_61_90'],
      'days_over_90' => $aging['days_over_90'],
      'po_count' => $supplierPOs->count(),
      'oldest_po_date' => $supplierPOs->min('po_date'),
      'largest_po_amount' => $supplierPOs->max('balance_due'),
    ];
  })->sortByDesc('total_outstanding');

  // Calculate summary totals
  $summary = [
    'total_outstanding' => $supplierAging->sum('total_outstanding'),
    'current' => $supplierAging->sum('current'),
    'days_1_30' => $supplierAging->sum('days_1_30'),
    'days_31_60' => $supplierAging->sum('days_31_60'),
    'days_61_90' => $supplierAging->sum('days_61_90'),
    'days_over_90' => $supplierAging->sum('days_over_90'),
    'supplier_count' => $supplierAging->count(),
  ];

  return [
    'as_of_date' => $asOfDate->format('d M Y'),
    'company' => $company->name,
    'suppliers' => $supplierAging->values()->toArray(),
    'summary' => $summary,
  ];
}
```

**Aging Buckets:**
- **Current:** 0 days overdue
- **1-30 days:** 1-30 days overdue
- **31-60 days:** 31-60 days overdue
- **61-90 days:** 61-90 days overdue
- **Over 90 days:** 90+ days overdue

#### ComparativeReportService (`app/Domain/Report/Services/ComparativeReportService.php`)

**Purpose:** Generates comparative financial reports (Year-over-Year, Period-over-Period)

**Methods:**

```php
public function yearOverYearProfitAndLoss(
  Company $company,
  Carbon $currentFromDate,
  Carbon $currentToDate,
  array $dimensions = []
): array {
  // Calculate previous year dates
  $previousFromDate = $currentFromDate->copy()->subYear();
  $previousToDate = $currentToDate->copy()->subYear();

  // Get current period P&L
  $currentPL = $this->dimensionalReportService->dimensionalProfitAndLoss(
    $company,
    $currentFromDate,
    $currentToDate,
    $dimensions
  );

  // Get previous period P&L
  $previousPL = $this->dimensionalReportService->dimensionalProfitAndLoss(
    $company,
    $previousFromDate,
    $previousToDate,
    $dimensions
  );

  // Calculate variances
  $revenueVariance = $this->calculateVariance(
    $currentPL['revenue']['total'],
    $previousPL['revenue']['total']
  );

  $expenseVariance = $this->calculateVariance(
    $currentPL['expenses']['total'],
    $previousPL['expenses']['total']
  );

  $netIncomeVariance = $this->calculateVariance(
    $currentPL['net_income'],
    $previousPL['net_income']
  );

  return [
    'comparison_type' => 'Year-over-Year',
    'current_period' => [
      'period' => $currentPL['period'],
      'revenue' => $currentPL['revenue']['total'],
      'expenses' => $currentPL['expenses']['total'],
      'net_income' => $currentPL['net_income'],
    ],
    'previous_period' => [
      'period' => $previousPL['period'],
      'revenue' => $previousPL['revenue']['total'],
      'expenses' => $previousPL['expenses']['total'],
      'net_income' => $previousPL['net_income'],
    ],
    'variance' => [
      'revenue' => $revenueVariance,
      'expenses' => $expenseVariance,
      'net_income' => $netIncomeVariance,
    ],
  ];
}
```

#### InventoryValuationReportService (`app/Domain/Report/Services/InventoryValuationReportService.php`)

**Purpose:** Generates comprehensive inventory valuation reports with COGS breakdown

**Methods:**

```php
public function inventoryValuation(Company $company, ?Carbon $asOfDate = null): array {
  $asOfDate = $asOfDate ?? now();

  // Get all stock layers as of the date
  $stockLayers = StockLayer::where('company_id', $company->id)
    ->where('layer_date', '<=', $asOfDate)
    ->where('qty_remaining', '>', 0)
    ->with(['product', 'warehouse'])
    ->get();

  $productValuation = $stockLayers->groupBy('product_id')->map(function ($productLayers, $productId) {
    $product = $productLayers->first()->product;
    
    $warehouseBreakdown = $productLayers->groupBy('warehouse_id')->map(function ($warehouseLayers, $warehouseId) {
      $warehouse = $warehouseLayers->first()->warehouse;
      $totalQty = $warehouseLayers->sum('qty_remaining');
      $totalValue = $warehouseLayers->sum(function ($layer) {
        return $layer->qty_remaining * $layer->unit_cost;
      });
      $avgCost = $totalQty > 0 ? $totalValue / $totalQty : 0;

      return [
        'warehouse_id' => $warehouseId,
        'warehouse_name' => $warehouse->name ?? 'Unknown Warehouse',
        'quantity' => $totalQty,
        'total_value' => $totalValue,
        'average_unit_cost' => round($avgCost, 2),
        'layer_count' => $warehouseLayers->count(),
        'oldest_layer_date' => $warehouseLayers->min('layer_date'),
        'newest_layer_date' => $warehouseLayers->max('layer_date'),
      ];
    });

    $totalQty = $productLayers->sum('qty_remaining');
    $totalValue = $productLayers->sum(function ($layer) {
      return $layer->qty_remaining * $layer->unit_cost;
    });
    $avgCost = $totalQty > 0 ? $totalValue / $totalQty : 0;

    return [
      'product_id' => $productId,
      'product_name' => $product->name ?? 'Unknown Product',
      'product_code' => $product->code ?? '',
      'unit' => $product->unit ?? 'pcs',
      'total_quantity' => $totalQty,
      'total_value' => $totalValue,
      'average_unit_cost' => round($avgCost, 2),
      'warehouse_breakdown' => $warehouseBreakdown->values()->toArray(),
      'layer_count' => $productLayers->count(),
    ];
  })->sortByDesc('total_value');

  // Calculate summary
  $summary = [
    'total_quantity' => $productValuation->sum('total_quantity'),
    'total_value' => $productValuation->sum('total_value'),
    'product_count' => $productValuation->count(),
    'warehouse_count' => $stockLayers->pluck('warehouse_id')->unique()->count(),
    'average_unit_cost' => $productValuation->count() > 0 
      ? $productValuation->sum('total_value') / $productValuation->sum('total_quantity') 
      : 0,
  ];

  return [
    'as_of_date' => $asOfDate->format('d M Y'),
    'company' => $company->name,
    'products' => $productValuation->values()->toArray(),
    'summary' => $summary,
  ];
}
```

#### VatLiabilityReportService (`app/Domain/Report/Services/VatLiabilityReportService.php`)

**Purpose:** Computes monthly VAT liability summary combining output VAT, input VAT credit, and net payable

**Methods:**

```php
public function generate(Company $company, int $month, int $year): array {
  $salesData = $this->mushak62->generate($company, $month, $year);
  $purchaseData = $this->mushak61->generate($company, $month, $year);
  $creditNoteData = $this->mushak66->generate($company, $month, $year);

  $totalOutputVat = collect($salesData)->sum('vat_amount');
  $totalSales = collect($salesData)->sum('taxable_value');

  $totalInputVat = collect($purchaseData)->sum('vat_amount');
  $totalPurchases = collect($purchaseData)->sum('taxable_value');

  $creditNoteVat = collect($creditNoteData)->sum('vat_amount');

  $netVatPayable = max(0, $totalOutputVat - $totalInputVat - $creditNoteVat);

  $period = Carbon::createFromDate($year, $month, 1)->format('F Y');

  return [
    'period' => $period,
    'company' => $company->name,
    'vat_bin' => $company->vat_bin,
    'output_vat' => [
      'total_sales' => $totalSales,
      'total_vat' => $totalOutputVat,
      'invoice_count' => count($salesData),
    ],
    'input_vat' => [
      'total_purchases' => $totalPurchases,
      'total_vat' => $totalInputVat,
      'receipt_count' => count($purchaseData),
    ],
    'adjustments' => [
      'credit_notes' => count($creditNoteData),
      'credit_note_vat' => $creditNoteVat,
    ],
    'summary' => [
      'gross_output_vat' => $totalOutputVat,
      'less_input_vat' => $totalInputVat,
      'less_adjustments' => $creditNoteVat,
      'net_vat_payable' => $netVatPayable,
    ],
  ];
}
```

#### Mushak61ReportService (`app/Domain/Report/Services/Mushak61ReportService.php`)

**Purpose:** Generates VAT input (Mushak 6.1 format) - Bangladesh VAT report for purchases

#### Mushak62ReportService (`app/Domain/Report/Services/Mushak62ReportService.php`)

**Purpose:** Generates VAT output (Mushak 6.2 format) - Bangladesh VAT report for sales

#### Mushak66Service (`app/Domain/Report/Services/Mushak66Service.php`)

**Purpose:** Credit notes (Mushak 6.6 format) - Bangladesh VAT credit notes

#### Mushak91Service (`app/Domain/Report/Services/Mushak91Service.php`)

**Purpose:** VAT returns (Mushak 9.1 format) - Bangladesh VAT monthly returns

#### BranchReportService (`app/Domain/Report/Services/BranchReportService.php`)

**Purpose:** Branch-wise reports

#### DimensionalReportService (`app/Domain/Report/Services/DimensionalReportService.php`)

**Purpose:** Dimensional reports with grouping by various dimensions

#### VatTransactionDetailReportService (`app/Domain/Report/Services/VatTransactionDetailReportService.php`)

**Purpose:** VAT transaction details

## Complete Data Flow

### Report Generation Flow

```
User requests report
    ↓
ReportService::generateReport()
    ├─→ Identify report type
    ├─→ Apply filters
    ├─→ Execute query
    ├─→ Apply aggregations
    ├─→ Apply grouping
    ├─→ Apply sorting
    ├─→ Add custom field values
    └─→ Return results
```

### Report Builder Flow

```
User creates report
    ↓
ReportBuilderService::getAvailableFields()
    ├─→ Get base fields for entity type
    ├─→ Get custom field definitions
    └─→ Return field list
    ↓
User selects fields, filters, aggregations
    ↓
SavedReport::create()
    ├─→ Save configuration
    ├─→ Set entity_type
    ├─→ Set selected_fields
    ├─→ Set filters
    ├─→ Set group_by
    ├─-> Set aggregate
    ├─→ Set sort_field
    ├─→ Set visualisation
    └─→ Set schedule
    ↓
ReportBuilderService::run()
    ├─→ Resolve model class
    ├─→ Build query
    ├─→ Apply filters
    ├─→ Apply grouping
    ├─-> Apply aggregations
    ├─→ Apply sorting
    ├─-> Execute query
    └─-> Add custom field values
```

### Cash Flow Report Flow

```
User requests cash flow statement
    ↓
CashFlowReportService::generateCashFlowStatement()
    ├─→ Get net income (Revenue - Expenses)
    ├─→ Get operating activities
    │   ├─→ Non-cash adjustments (depreciation, amortization, bad debt)
    │   └─→ Working capital changes (AR, inventory, prepaid, AP, accrued, deferred)
    ├─→ Get investing activities
    │   ├─→ Capital expenditures
    │   ├─-> Asset sales
    │   └─→ Investments
    ├─→ Get financing activities
    │   ├─→ Loans
    │   ├─-> Equity
    │   └─→ Dividends
    ├─→ Calculate net change in cash
    ├─→ Get beginning cash balance
    ├─-> Get ending cash balance
    └─→ Return cash flow statement
```

### Aging Report Flow

```
User requests aging report
    ↓
AgingReportService::accountsReceivableAging()
    ├─→ Get unpaid/partially paid invoices
    ├─→ Group by customer
    ├─→ Calculate aging buckets
    │   ├─→ Current (0 days)
    │   ├─→ 1-30 days
    │   ├─→ 31-60 days
    │   ├─-> 61-90 days
    │   └─→ Over 90 days
    ├─→ Calculate summary totals
    └─-> Return aging report
```

### Inventory Valuation Flow

```
User requests inventory valuation
    ↓
InventoryValuationReportService::inventoryValuation()
    ├─→ Get stock layers as of date
    ├─-> Group by product
    ├─→ Calculate warehouse breakdown
    ├─→ Calculate total quantity/value
    ├─→ Calculate average unit cost
    ├─→ Calculate summary totals
    └─-> Return valuation report
```

### VAT Liability Flow

```
User requests VAT liability
    ↓
VatLiabilityReportService::generate()
    ├─→ Get Mushak 6.2 data (output VAT)
    ├─→ Get Mushak 6.1 data (input VAT)
    ├─→ Get Mushak 6.6 data (credit notes)
    ├─→ Calculate total output VAT
    ├─-> Calculate total input VAT
    ├─→ Calculate credit note VAT
    ├─→ Calculate net VAT payable
    └─-> Return VAT liability report
```

## Integration with Other Domains

### Accounting Domain

**Financial Statements:**
- Trial Balance
- Profit & Loss Statement
- Balance Sheet
- Cash Flow Statement

**Journal Entry Integration:**
```php
// Cash Flow Report
JournalEntryLine::query()
  ->whereHas('journalEntry', function ($q) use ($company, $fromDate, $toDate) {
    $q->where('company_id', $company->id)
      ->where('status', 'posted')
      ->whereBetween('entry_date', [$fromDate, $toDate]);
  })
  ->whereHas('account', function ($q) {
    $q->whereBetween('account_code', ['4000', '4999']); // Revenue
  })
  ->sum(\DB::raw('credit - debit'));
```

### Sales Domain

**Aging Reports:**
```php
Invoice::where('company_id', $company->id)
  ->whereIn('status', ['sent', 'partial'])
  ->where('invoice_date', '<=', $asOfDate)
  ->where('balance_due', '>', 0)
  ->with(['customer'])
  ->get();
```

### Purchase Domain

**Aging Reports:**
```php
PurchaseOrder::where('company_id', $company->id)
  ->whereIn('status', ['sent', 'partial'])
  ->where('po_date', '<=', $asOfDate)
  ->where('balance_due', '>', 0)
  ->with(['supplier'])
  ->get();
```

### Inventory Domain

**Inventory Valuation:**
```php
StockLayer::where('company_id', $company->id)
  ->where('layer_date', '<=', $asOfDate)
  ->where('qty_remaining', '>', 0)
  ->with(['product', 'warehouse'])
  ->get();
```

### Product Domain

**Report Builder:**
```php
Product::query()
  ->select($selectedFields)
  ->where($filters)
  ->groupBy($groupBy)
  ->orderBy($sortField, $sortDirection)
  ->get();
```

### Customer Domain

**Report Builder:**
```php
Customer::query()
  ->select($selectedFields)
  ->where($filters)
  ->groupBy($groupBy)
  ->orderBy($sortField, $sortDirection)
  ->get();
```

## Comparison with Modern Reporting Systems

### Features Comparison

| Feature | This System | QuickBooks | Xero | SAP |
|---------|-------------|-----------|------|-----|
| **Financial Statements** | ✅ | ✅ | ✅ | ✅ |
| **Cash Flow Statement** | ✅ | ✅ | ✅ | ✅ |
| **Aging Reports** | ✅ | ✅ | ✅ | ✅ |
| **Inventory Valuation** | ✅ | ✅ | ✅ | ✅ |
| **VAT Reports** | ✅ | ✅ | ✅ | ✅ |
| **Custom Report Builder** | ✅ | ✅ | ✅ | ✅ |
| **Scheduled Reports** | ✅ | ✅ | ✅ | ✅ |
| **Email Reports** | ✅ | ✅ | ✅ | ✅ |
| **Export to PDF/Excel** | ⚠️ | ✅ | ✅ | ✅ |
| **Dashboard Widgets** | ⚠️ | ✅ | ✅ | ✅ |
| **Comparative Reports** | ✅ | ✅ | ✅ | ✅ |
| **Dimensional Reports** | ✅ | ✅ | ✅ | ✅ |
| **Multi-dimensional Analysis** | ✅ | ✅ | ✅ | ✅ |
| **Drill-down** | ⚠️ | ✅ | ✅ | ✅ |
| **Budget vs Actual** | ⚠️ | ✅ | ✅ | ✅ |
| **Forecasting** | ❌ | ✅ | ✅ | ✅ |
| **KPI Tracking** | ⚠️ | ✅ | ✅ | ✅ |
| **Custom Formulas** | ⚠️ | ✅ | ✅ | ✅ |
| **Report Sharing** | ⚠️ | ✅ | ✅ | ✅ |
| **API Access** | ✅ | ✅ | ✅ | ✅ |
| **Real-time Updates** | ⚠️ | ✅ | ✅ | ✅ |

### Workflow Comparison

**This System:**
```
Report Request → Service → Query → Aggregation → Return Data
```

**QuickBooks/Xero:**
```
Report Request → Service → Query → Aggregation → Format → Export → Email
```

**SAP:**
```
Report Request → Service → Query → Aggregation → Drill-down → Format → Export → Email
```

### Unique Features

**This System:**
- Bangladesh-specific VAT reports (Mushak 6.1, 6.2, 6.6, 9.1)
- Multi-tenancy support
- Custom field integration
- Report builder with entity types
- Scheduled reports with email
- Comparative reports (YoY, PoP)
- Inventory valuation with warehouse breakdown

**QuickBooks/Xero:**
- More export options (PDF, Excel, CSV)
- Dashboard widgets
- Budget vs actual
- Forecasting
- KPI tracking
- Custom formulas
- Report sharing
- Real-time updates

**SAP:**
- Drill-down capabilities
- Advanced dimensional analysis
- Complex calculations
- Multi-currency
- Consolidation
- Workflow integration

## API Reference

### Reports

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/reports/{reportId}` | Generate report | Required |
| POST | `/api/v1/reports/custom` | Create custom report | Required |
| GET | `/api/v1/reports/saved` | List saved reports | Required |
| GET | `/api/v1/reports/saved/{id}` | Get saved report | Required |
| POST | `/api/v1/reports/saved` | Save report | Required |
| PUT | `/api/v1/reports/saved/{id}` | Update saved report | Required |
| DELETE | `/api/v1/reports/saved/{id}` | Delete saved report | Required |
| POST | `/api/v1/reports/saved/{id}/run` | Run saved report | Required |

### Report Types

**Financial Reports:**
- `trial_balance` - Trial Balance
- `profit_loss` - Profit & Loss Statement
- `balance_sheet` - Balance Sheet
- `cash_flow` - Cash Flow Statement

**Aging Reports:**
- `aging_ar` - Accounts Receivable Aging
- `aging_ap` - Accounts Payable Aging

**Inventory Reports:**
- `inventory_valuation` - Inventory Valuation

**VAT Reports:**
- `vat_liability` - VAT Liability
- `mushak61` - VAT Input (Mushak 6.1)
- `mushak62` - VAT Output (Mushak 6.2)
- `mushak66` - Credit Notes (Mushak 6.6)
- `mushak91` - VAT Returns (Mushak 9.1)

**Comparative Reports:**
- `yoy_profit_loss` - Year-over-Year P&L
- `pop_profit_loss` - Period-over-Period P&L

### Query Parameters

```
from_date -> From date
to_date -> To date
as_of_date -> As of date
month -> Month
year -> Year
company_id -> Company ID
entity_type -> Entity type
filters -> Filters (JSON)
group_by -> Group by field
aggregate -> Aggregations (JSON)
sort_by -> Sort field
sort_order -> Sort order (asc/desc)
limit -> Limit
offset -> Offset
```

### Request Body (Save Report)

```json
{
  "name": "Customer Aging Report",
  "entity_type": "customer",
  "selected_fields": ["id", "name", "email", "balance"],
  "filters": {
    "balance": { "operator": ">", "value": 0 }
  },
  "group_by": "district",
  "aggregate": [
    { "field": "balance", "function": "sum", "alias": "total_balance" }
  ],
  "sort_field": "total_balance",
  "sort_direction": "desc",
  "visualisation": "table",
  "is_scheduled": true,
  "schedule_frequency": "weekly",
  "schedule_recipients": ["admin@example.com"]
}
```

### Response Format

```json
{
  "success": true,
  "data": {
    "columns": ["id", "name", "email", "balance"],
    "rows": [
      {
        "id": 1,
        "name": "Customer A",
        "email": "customer@example.com",
        "balance": 50000
      }
    ],
    "total": 1
  },
  "message": "Report generated"
}
```

## Frontend Integration

### Report Builder

```javascript
const fetchAvailableFields = async (entityType) => {
  const response = await get(`/reports/fields/${entityType}`)
  return response.data
}

const createReport = async (reportData) => {
  const response = await post('/reports/saved', reportData)
  return response.data
}

const runReport = async (reportId) => {
  const response = await post(`/reports/saved/${reportId}/run`)
  return response.data
}
```

### Financial Reports

```javascript
const fetchCashFlowStatement = async (fromDate, toDate) => {
  const response = await get('/reports/cash_flow', {
    from_date: fromDate,
    to_date: toDate,
  })
  return response.data
}

const fetchProfitLoss = async (fromDate, toDate) => {
  const response = await get('/reports/profit_loss', {
    from_date: fromDate,
    to_date: toDate,
  })
  return response.data
}
```

### Aging Reports

```javascript
const fetchAgingReport = async (type, asOfDate) => {
  const response = await get(`/reports/aging/${type}`, {
    as_of_date: asOfDate,
  })
  return response.data
}
```

### VAT Reports

```javascript
const fetchVatLiability = async (month, year) => {
  const response = await get('/reports/vat_liability', {
    month,
    year,
  })
  return response.data
}
```

## Frontend Architecture

### Components Created

**1. Reports Dashboard** (`resources/js/Pages/Reports/Dashboard/Index.vue`)
- Quick stats (Financial, Aging, VAT, Custom reports)
- Quick access buttons to all report types
- Recent reports list with view/download actions

**2. Financial Reports**
- `TrialBalance.vue` - Trial balance with date filter, PDF/Excel download
- `ProfitLoss.vue` - P&L statement with period range, revenue/expenses breakdown
- `BalanceSheet.vue` - Balance sheet with assets, liabilities, equity sections
- `CashFlow.vue` - Cash flow statement (operating, investing, financing activities)

**3. Aging Reports**
- `AccountsReceivable.vue` - AR aging with customer breakdown, aging buckets (Current, 1-30, 31-60, 61-90, 90+)
- `AccountsPayable.vue` - AP aging with supplier breakdown, same aging buckets

**4. Inventory Reports**
- `Valuation.vue` - Inventory valuation with warehouse breakdown, layer analysis

**5. VAT Reports**
- `Liability.vue` - VAT liability with output/input VAT, adjustments, net payable
- `Mushak63.vue` - Mushak 6.3 VAT invoice PDF generation with preview

**6. Comparative Reports**
- `YoYProfitLoss.vue` - Year-over-Year P&L comparison with variance analysis

**7. Report Builder**
- `Builder/Index.vue` - Custom report builder with entity types, field selection, filters, scheduling

### Features Included

- **Date Filters** - All reports have date range filters
- **PDF/Excel Download** - Export functionality for all reports
- **Loading States** - Disabled buttons during report generation
- **Currency Formatting** - BDT currency display
- **Responsive Design** - Mobile-friendly layouts
- **Summary Cards** - Key metrics displayed prominently
- **Tables** - Data tables with proper formatting
- **Status Colors** - Green for good, yellow/orange for warning, red for critical

## Summary

### Backend Coverage
- ✅ SavedReport model (report configurations, scheduling)
- ✅ ReportService (basic financial statements)
- ✅ ReportBuilderService (custom reports, entity types, filters, aggregations)
- ✅ CashFlowReportService (cash flow statement)
- ✅ AgingReportService (AR/AP aging)
- ✅ ComparativeReportService (YoY, PoP comparisons)
- ✅ InventoryValuationReportService (inventory valuation)
- ✅ VatLiabilityReportService (VAT liability)
- ✅ Mushak61ReportService (VAT input)
- ✅ Mushak62ReportService (VAT output)
- ✅ Mushak66Service (credit notes)
- ✅ Mushak91Service (VAT returns)
- ✅ BranchReportService (branch reports)
- ✅ DimensionalReportService (dimensional reports)
- ✅ VatTransactionDetailReportService (VAT details)
- ✅ Multi-tenancy support

### Integration
- ✅ Accounting Domain (financial statements, journal entries)
- ✅ Sales Domain (aging reports, invoices)
- ✅ Purchase Domain (aging reports, purchase orders)
- ✅ Inventory Domain (inventory valuation, stock layers)
- ✅ Product Domain (report builder)
- ✅ Customer Domain (report builder)
- ✅ Multi-tenancy (company_id isolation)

### Features
- ✅ Financial Statements (Trial Balance, P&L, Balance Sheet, Cash Flow)
- ✅ Aging Reports (Accounts Receivable, Accounts Payable)
- ✅ Inventory Valuation (with warehouse breakdown)
- ✅ VAT Reports (Liability, Mushak 6.1, 6.2, 6.6, 9.1)
- ✅ Comparative Reports (Year-over-Year, Period-over-Period)
- ✅ Report Builder (custom reports, entity types, filters, aggregations)
- ✅ Scheduled Reports (daily, weekly, monthly)
- ✅ Email Reports (recipients)
- ✅ Custom Field Integration
- ✅ Multi-dimensional Analysis
- ✅ Bangladesh-specific VAT formats

The Reports system provides **comprehensive reporting capabilities** with financial statements, aging reports, inventory valuation, VAT reports, and a flexible report builder for custom reports, all with Bangladesh-specific VAT formats and multi-tenancy support.

## Backend Architecture
- **SavedReport Model** - Report configurations with entity_type, selected_fields, filters, group_by, aggregate, visualisation, scheduling
- **ReportService** - Basic financial statements (Trial Balance, P&L, Balance Sheet)
- **ReportBuilderService** - Custom reports with entity types (customer, product, invoice, purchase, employee, expense, supplier), filters, aggregations, custom field integration
- **CashFlowReportService** - Cash Flow Statement using indirect method (Operating, Investing, Financing activities)
- **AgingReportService** - Accounts Receivable/Payable aging with buckets (Current, 1-30, 31-60, 61-90, Over 90 days)
- **ComparativeReportService** - Year-over-Year, Period-over-Period comparisons with variance calculations
- **InventoryValuationReportService** - Inventory valuation with warehouse breakdown, COGS, layer analysis
- **VatLiabilityReportService** - Monthly VAT liability (output VAT, input VAT, credit notes, net payable)
- **Mushak Services** - Bangladesh-specific VAT formats (6.1 input, 6.2 output, 6.6 credit notes, 9.1 returns)

## Data Flows
- **Report Generation:** Identify type → Apply filters → Execute query → Apply aggregations/grouping/sorting → Add custom fields → Return results
- **Report Builder:** Get fields → User selects configuration → Save report → Run report with query building
- **Cash Flow:** Net income → Operating activities → Investing activities → Financing activities → Net change → Beginning/ending cash
- **Aging:** Get outstanding invoices/POs → Group by customer/supplier → Calculate aging buckets → Summary totals
- **Inventory Valuation:** Get stock layers → Group by product → Warehouse breakdown → Calculate totals/values
- **VAT Liability:** Mushak 6.2 (output) + Mushak 6.1 (input) + Mushak 6.6 (credit notes) → Net payable

## Integration
- **Accounting Domain:** Financial statements, journal entries
- **Sales Domain:** Aging reports, invoices
- **Purchase Domain:** Aging reports, purchase orders
- **Inventory Domain:** Inventory valuation, stock layers
- **Product/Customer Domain:** Report builder queries

## Features
- ✅ Financial Statements (Trial Balance, P&L, Balance Sheet, Cash Flow)
- ✅ Aging Reports (AR/AP)
- ✅ Inventory Valuation (warehouse breakdown)
- ✅ VAT Reports (Liability, Mushak 6.1/6.2/6.6/9.1)
- ✅ Comparative Reports (YoY, PoP)
- ✅ Report Builder (custom reports, 8 entity types)
- ✅ Scheduled Reports (daily/weekly/monthly)
- ✅ Email Reports
- ✅ Custom Field Integration
- ✅ Multi-dimensional Analysis
- ✅ Bangladesh-specific VAT formats

## Comparison
- **Similar:** Financial statements, aging reports, inventory valuation, VAT reports, custom reports, scheduled reports
- **Simpler:** No PDF/Excel export, no dashboard widgets, no budget vs actual, no forecasting, no KPI tracking, no drill-down
- **Unique:** Bangladesh-specific VAT formats (Mushak), multi-tenancy, custom field integration, warehouse breakdown in inventory valuation