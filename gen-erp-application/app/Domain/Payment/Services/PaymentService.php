<?php

namespace App\Domain\Payment\Services;

use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Models\CustomerPayment;
use App\Domain\Customer\Models\CustomerTransaction;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\Purchase\Models\Supplier;
use App\Domain\Customer\Models\CreditNote;
use App\Domain\Purchase\Models\GoodsReceipt;
use App\Domain\Purchase\Models\PurchaseReturn;
use App\Domain\Customer\Models\SalesReturn;
use App\Domain\Purchase\Models\SupplierPayment;
use App\Domain\Inventory\Services\InventoryService;
use App\Domain\System\Services\SequenceService;
use App\Support\Enums\CreditNoteStatus;
use App\Support\Enums\InvoiceStatus;
use App\Support\Enums\StockMovementType;
use Illuminate\Support\Facades\DB;

/**
 * Manages payment operations - customer payments, supplier payments, returns.
 */
class PaymentService
{
    public function __construct(
        private SequenceService $sequenceService,
        private InventoryService $inventoryService
    ) {}

    /**
     * Receive payment from customer.
     */
    public function receivePayment(Customer $customer, array $paymentData, array $allocations = []): CustomerPayment
    {
        return DB::transaction(function () use ($customer, $paymentData, $allocations) {
            // Validate allocations don't exceed payment amount
            $totalAllocated = array_sum(array_column($allocations, 'amount'));
            if ($totalAllocated > $paymentData['amount']) {
                throw new \InvalidArgumentException('Total allocation exceeds payment amount.');
            }

            // Create payment record
            $payment = CustomerPayment::create([
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id,
                'receipt_number' => $this->sequenceService->next('rcp', $customer->company_id),
                'payment_date' => $paymentData['payment_date'],
                'amount' => $paymentData['amount'],
                'payment_method' => $paymentData['payment_method'] ?? 'cash',
                'reference' => $paymentData['reference'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
            ]);

            // Apply allocations to invoices
            foreach ($allocations as $allocation) {
                $invoice = Invoice::findOrFail($allocation['invoice_id']);
                
                $payment->allocations()->create([
                    'company_id' => $customer->company_id,
                    'invoice_id' => $invoice->id,
                    'allocated_amount' => $allocation['amount'],
                ]);

                // Update invoice amount paid
                $invoice->increment('amount_paid', $allocation['amount']);

                // Update invoice status based on payment
                if ($invoice->amount_paid >= $invoice->total_amount) {
                    $invoice->update(['status' => InvoiceStatus::PAID]);
                } elseif ($invoice->amount_paid > 0) {
                    $invoice->update(['status' => InvoiceStatus::PARTIAL]);
                }
            }

            return $payment;
        });
    }

    /**
     * Make payment to supplier.
     */
    public function makePayment(Supplier $supplier, array $paymentData): SupplierPayment
    {
        return DB::transaction(function () use ($supplier, $paymentData) {
            $grossAmount = $paymentData['gross_amount'];
            $tdsAmount = (int) round($grossAmount * ($supplier->tds_rate / 100));
            $vdsAmount = (int) round($grossAmount * ($supplier->vds_rate / 100));
            $netAmount = $grossAmount - $tdsAmount - $vdsAmount;

            return SupplierPayment::create([
                'company_id' => $supplier->company_id,
                'supplier_id' => $supplier->id,
                'payment_number' => $this->sequenceService->next('supplier_payment', $supplier->company_id),
                'payment_date' => $paymentData['payment_date'],
                'gross_amount' => $grossAmount,
                'tds_amount' => $tdsAmount,
                'vds_amount' => $vdsAmount,
                'net_amount' => $netAmount,
                'payment_method' => $paymentData['payment_method'] ?? 'bank',
                'reference' => $paymentData['reference'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
            ]);
        });
    }

    /**
     * Issue a credit note.
     */
    public function issueCreditNote(Invoice $invoice, array $data, array $items): CreditNote
    {
        return DB::transaction(function () use ($invoice, $data, $items) {
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $creditNote = CreditNote::create([
                'company_id' => $invoice->company_id,
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'credit_number' => $this->sequenceService->next('credit_note', $invoice->company_id),
                'credit_date' => $data['credit_date'],
                'reason' => $data['reason'],
                'total_amount' => $subtotal,
                'status' => CreditNoteStatus::ISSUED,
            ]);

            foreach ($items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $creditNote->items()->create([
                    'company_id' => $invoice->company_id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $lineTotal,
                ]);
            }

            return $creditNote->load('items');
        });
    }

    /**
     * Apply credit note to invoice.
     */
    public function applyCreditNote(CreditNote $creditNote, Invoice $invoice): void
    {
        DB::transaction(function () use ($creditNote, $invoice) {
            $invoice->increment('amount_paid', $creditNote->total_amount);
            $creditNote->update(['status' => CreditNoteStatus::APPLIED]);

            // Update invoice status
            if ($invoice->amount_paid >= $invoice->total_amount) {
                $invoice->update(['status' => InvoiceStatus::PAID]);
            } elseif ($invoice->amount_paid > 0) {
                $invoice->update(['status' => InvoiceStatus::PARTIAL]);
            }
        });
    }

    /**
     * Create sales return.
     */
    public function createSalesReturn(Invoice $invoice, array $items, int $warehouseId): SalesReturn
    {
        return DB::transaction(function () use ($invoice, $items, $warehouseId) {
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            $return = SalesReturn::create([
                'company_id' => $invoice->company_id,
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'warehouse_id' => $warehouseId,
                'return_number' => $this->sequenceService->next('sales_return', $invoice->company_id),
                'return_date' => now()->toDateString(),
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            foreach ($items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $return->items()->create([
                    'company_id' => $invoice->company_id,
                    'product_id' => $item['product_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $lineTotal,
                ]);
            }

            return $return->load('items');
        });
    }

    /**
     * Approve sales return and restore stock.
     */
    public function approveSalesReturn(SalesReturn $return): void
    {
        DB::transaction(function () use ($return) {
            foreach ($return->items as $item) {
                if ($item->product_id) {
                    $this->inventoryService->stockIn(
                        $return->warehouse_id,
                        $item->product_id,
                        $item->quantity,
                        StockMovementType::SALE_RETURN,
                        $item->variant_id, // Use the item's variant_id
                        null, // unit_cost
                        "Sales return {$return->return_number}", // notes
                        $return // reference
                    );
                }
            }

            $return->update([
                'status' => 'approved',
                'stock_restored' => true,
            ]);
        });
    }

    /**
     * Create purchase return.
     */
    public function createPurchaseReturn(GoodsReceipt $receipt, array $items): PurchaseReturn
    {
        return DB::transaction(function () use ($receipt, $items) {
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += $item['quantity'] * $item['unit_cost'];
            }

            $return = PurchaseReturn::create([
                'company_id' => $receipt->company_id,
                'supplier_id' => $receipt->supplier_id,
                'goods_receipt_id' => $receipt->id,
                'warehouse_id' => $receipt->warehouse_id,
                'return_number' => $this->sequenceService->next('purchase_return', $receipt->company_id),
                'return_date' => now()->toDateString(),
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            foreach ($items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_cost'];
                $return->items()->create([
                    'company_id' => $receipt->company_id,
                    'product_id' => $item['product_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $lineTotal,
                ]);
            }

            return $return->load('items');
        });
    }

    /**
     * Approve purchase return and remove stock.
     */
    public function approvePurchaseReturn(PurchaseReturn $return): void
    {
        DB::transaction(function () use ($return) {
            foreach ($return->items as $item) {
                if ($item->product_id) {
                    $this->inventoryService->stockOut(
                        $return->warehouse_id,
                        $item->product_id,
                        $item->quantity,
                        StockMovementType::PURCHASE_RETURN,
                        $item->variant_id, // Use the item's variant_id
                        "Purchase return {$return->return_number}", // notes
                        $return // reference
                    );
                }
            }

            $return->update([
                'status' => 'approved',
                'stock_removed' => true,
            ]);
        });
    }
}