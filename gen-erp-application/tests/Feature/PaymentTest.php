<?php

use App\Enums\CreditNoteStatus;
use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\CompanyContext;
use App\Services\Mushak62ReportService;
use App\Services\PaymentService;

// ═══════════════════════════════════════════════════
// PaymentTest — 12 tests
// ═══════════════════════════════════════════════════

test('receivePayment creates payment and updates invoice.amount_paid', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'total_amount' => 1000000,
        'amount_paid' => 0,
        'status' => InvoiceStatus::SENT,
    ]);

    $service = app(PaymentService::class);
    $payment = $service->receivePayment($customer, [
        'payment_date' => now()->toDateString(),
        'amount' => 500000,
    ], [
        ['invoice_id' => $invoice->id, 'amount' => 500000],
    ]);

    expect($payment->receipt_number)->toStartWith('RCP-');
    expect($payment->amount)->toBe(500000);
    expect($invoice->fresh()->amount_paid)->toBe(500000);
});

test('full payment sets invoice status to paid', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'total_amount' => 200000,
        'amount_paid' => 0,
        'status' => InvoiceStatus::SENT,
    ]);

    $service = app(PaymentService::class);
    $service->receivePayment($customer, [
        'payment_date' => now()->toDateString(),
        'amount' => 200000,
    ], [
        ['invoice_id' => $invoice->id, 'amount' => 200000],
    ]);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
});

test('partial payment sets invoice status to partial', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'total_amount' => 500000,
        'amount_paid' => 0,
        'status' => InvoiceStatus::SENT,
    ]);

    $service = app(PaymentService::class);
    $service->receivePayment($customer, [
        'payment_date' => now()->toDateString(),
        'amount' => 100000,
    ], [
        ['invoice_id' => $invoice->id, 'amount' => 100000],
    ]);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PARTIAL);
});

test('over-allocation throws exception', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'total_amount' => 100000,
        'amount_paid' => 0,
        'status' => InvoiceStatus::SENT,
    ]);

    $service = app(PaymentService::class);

    $service->receivePayment($customer, [
        'payment_date' => now()->toDateString(),
        'amount' => 50000,
    ], [
        ['invoice_id' => $invoice->id, 'amount' => 60000],
    ]);
})->throws(InvalidArgumentException::class, 'Total allocation exceeds payment amount.');

test('TDS/VDS deductions calculated correctly on supplier payment', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $supplier = Supplier::factory()->create([
        'company_id' => $company->id,
        'tds_rate' => 5.0,
        'vds_rate' => 7.5,
    ]);

    $service = app(PaymentService::class);
    $payment = $service->makePayment($supplier, [
        'payment_date' => now()->toDateString(),
        'gross_amount' => 1000000,
    ]);

    expect($payment->gross_amount)->toBe(1000000);
    expect($payment->tds_amount)->toBe(50000);
    expect($payment->vds_amount)->toBe(75000);
    expect($payment->fresh()->net_amount)->toBe(875000);
});

test('applyCreditNote reduces invoice balance_due', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'total_amount' => 500000,
        'amount_paid' => 0,
        'status' => InvoiceStatus::SENT,
    ]);

    $service = app(PaymentService::class);
    $creditNote = $service->issueCreditNote($invoice, [
        'credit_date' => now()->toDateString(),
        'reason' => 'Damaged goods',
    ], [
        ['description' => 'Damaged item', 'quantity' => 2, 'unit_price' => 100000],
    ]);

    $service->applyCreditNote($creditNote, $invoice);

    expect($invoice->fresh()->amount_paid)->toBe(200000);
    expect($creditNote->fresh()->status)->toBe(CreditNoteStatus::APPLIED);
});

test('approveSalesReturn restores stock via InventoryService', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'track_inventory' => true]);

    // First add stock via InventoryService so we can deduct it
    $inventoryService = app(\App\Services\InventoryService::class);
    $inventoryService->stockIn($warehouse->id, $product->id, 100, \App\Enums\StockMovementType::PURCHASE_RECEIPT, null, 10000);
    $inventoryService->stockOut($warehouse->id, $product->id, 30, \App\Enums\StockMovementType::SALE, null, 15000);

    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'total_amount' => 450000,
        'status' => InvoiceStatus::SENT,
    ]);

    $service = app(PaymentService::class);
    $return = $service->createSalesReturn($invoice, [
        ['product_id' => $product->id, 'description' => 'Returned Widget', 'quantity' => 10, 'unit_price' => 15000],
    ], $warehouse->id);

    $service->approveSalesReturn($return);

    // Stock was 70 (100 - 30), return restores 10 → 80
    $level = \App\Models\StockLevel::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->first();
    expect($level->quantity)->toBe(80.0);
    expect($return->fresh()->stock_restored)->toBeTrue();
});

test('approvePurchaseReturn removes stock correctly', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'track_inventory' => true]);

    // Add stock first
    $inventoryService = app(\App\Services\InventoryService::class);
    $inventoryService->stockIn($warehouse->id, $product->id, 50, \App\Enums\StockMovementType::PURCHASE_RECEIPT, null, 20000);

    $receipt = GoodsReceipt::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'receipt_date' => now()->toDateString(),
        'status' => 'posted',
        'subtotal' => 1000000,
        'tax_amount' => 0,
        'total_amount' => 1000000,
        'stock_added' => true,
    ]);

    $service = app(PaymentService::class);
    $return = $service->createPurchaseReturn($receipt, [
        ['product_id' => $product->id, 'description' => 'Defective Material', 'quantity' => 15, 'unit_cost' => 20000],
    ]);

    $service->approvePurchaseReturn($return);

    // Stock was 50, return removes 15 → 35
    $level = \App\Models\StockLevel::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->first();
    expect($level->quantity)->toBe(35.0);
    expect($return->fresh()->stock_removed)->toBeTrue();
});

test('Mushak 6.2 output_vat minus input_vat equals correct net_vat_payable', function (): void {
    $company = Company::factory()->create(['vat_registered' => true]);
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);

    // Create an invoice with tax
    Invoice::factory()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'total_amount' => 1150000,
        'tax_amount' => 150000,
        'status' => InvoiceStatus::SENT,
        'invoice_date' => now()->toDateString(),
    ]);

    // Create a posted GRN with tax
    GoodsReceipt::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'receipt_date' => now()->toDateString(),
        'status' => 'posted',
        'subtotal' => 500000,
        'tax_amount' => 75000,
        'total_amount' => 575000,
        'stock_added' => true,
    ]);

    $report = app(Mushak62ReportService::class);
    $summary = $report->generateSummary($company, (int) now()->format('m'), (int) now()->format('Y'));

    expect($summary['total_output_vat'])->toBe(150000);
    expect($summary['total_input_vat'])->toBe(75000);
    expect($summary['net_vat_payable'])->toBe(75000);
});

test('MarkOverdueInvoicesCommand updates correct invoices and skips paid ones', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);

    // Overdue invoice (past due, sent)
    $overdueInv = Invoice::factory()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'total_amount' => 100000,
        'status' => InvoiceStatus::SENT,
        'due_date' => now()->subDays(5)->toDateString(),
    ]);

    // Paid invoice (past due but paid — should NOT be marked overdue)
    $paidInv = Invoice::factory()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'total_amount' => 100000,
        'amount_paid' => 100000,
        'status' => InvoiceStatus::PAID,
        'due_date' => now()->subDays(5)->toDateString(),
    ]);

    // Future invoice (not yet due)
    $futureInv = Invoice::factory()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'total_amount' => 100000,
        'status' => InvoiceStatus::SENT,
        'due_date' => now()->addDays(30)->toDateString(),
    ]);

    Artisan::call('invoices:mark-overdue');

    expect($overdueInv->fresh()->status)->toBe(InvoiceStatus::OVERDUE);
    expect($paidInv->fresh()->status)->toBe(InvoiceStatus::PAID);
    expect($futureInv->fresh()->status)->toBe(InvoiceStatus::SENT);
});

test('Company A payments not visible to Company B', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();
    $customerA = Customer::factory()->create(['company_id' => $companyA->id]);

    CompanyContext::setActive($companyA);
    $service = app(PaymentService::class);
    $service->receivePayment($customerA, [
        'payment_date' => now()->toDateString(),
        'amount' => 50000,
    ]);

    expect(CustomerPayment::all())->toHaveCount(1);

    CompanyContext::setActive($companyB);
    expect(CustomerPayment::all())->toHaveCount(0);
});

test('unallocated payment amount tracked correctly after partial allocation', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $customer = Customer::factory()->create(['company_id' => $company->id]);

    $invoice1 = Invoice::factory()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'total_amount' => 300000,
        'amount_paid' => 0,
        'status' => InvoiceStatus::SENT,
    ]);

    $service = app(PaymentService::class);
    $payment = $service->receivePayment($customer, [
        'payment_date' => now()->toDateString(),
        'amount' => 500000,
    ], [
        ['invoice_id' => $invoice1->id, 'amount' => 300000],
    ]);

    // 500000 paid, 300000 allocated → 200000 unallocated
    expect($payment->unallocatedAmount())->toBe(200000);
});
