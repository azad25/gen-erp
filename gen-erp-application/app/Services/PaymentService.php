<?php

namespace App\Services;

use App\Enums\CreditNoteStatus;
use App\Enums\InvoiceStatus;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\CustomerPaymentAllocation;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\SupplierPaymentAllocation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Orchestrates customer payments, supplier payments, credit notes, and returns.
 */
class PaymentService
{
    public function __construct(
        private readonly ContactService $contactService,
    ) {}

    // ═══════════════════════════════════════════════
    // Customer Payments
    // ═══════════════════════════════════════════════

    /**
     * Receive a payment from a customer and allocate against invoices.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array{invoice_id: int, amount: int}>  $allocations
     */
    public function receivePayment(Customer $customer, array $data, array $allocations = []): CustomerPayment
    {
        return DB::transaction(function () use ($customer, $data, $allocations): CustomerPayment {
            $payment = CustomerPayment::withoutGlobalScopes()->create(array_merge($data, [
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id,
                'created_by' => auth()->id(),
            ]));

            $totalAllocated = 0;

            foreach ($allocations as $alloc) {
                $totalAllocated += (int) $alloc['amount'];
            }

            if ($totalAllocated > $payment->amount) {
                throw new InvalidArgumentException(__('Total allocation exceeds payment amount.'));
            }

            foreach ($allocations as $alloc) {
                $this->allocatePayment($payment, $alloc['invoice_id'], (int) $alloc['amount']);
            }

            // Record customer transaction (credit — they paid us, reduces balance)
            $this->contactService->recordCustomerTransaction(
                $customer,
                'payment',
                -$payment->amount,
                "Payment {$payment->receipt_number}",
                $payment,
            );

            return $payment;
        });
    }

    /**
     * Allocate a payment amount to a specific invoice.
     */
    public function allocatePayment(CustomerPayment $payment, int $invoiceId, int $amount): void
    {
        CustomerPaymentAllocation::withoutGlobalScopes()->create([
            'customer_payment_id' => $payment->id,
            'company_id' => $payment->company_id,
            'invoice_id' => $invoiceId,
            'allocated_amount' => $amount,
        ]);

        $invoice = Invoice::withoutGlobalScopes()->findOrFail($invoiceId);
        $invoice->update(['amount_paid' => $invoice->amount_paid + $amount]);

        // Recalculate invoice status
        if ($invoice->amount_paid >= $invoice->total_amount) {
            $invoice->update(['status' => InvoiceStatus::PAID]);
        } elseif ($invoice->amount_paid > 0) {
            $invoice->update(['status' => InvoiceStatus::PARTIAL]);
        }
    }

    // ═══════════════════════════════════════════════
    // Supplier Payments
    // ═══════════════════════════════════════════════

    /**
     * Make a payment to a supplier with TDS/VDS deductions.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array{purchase_order_id: int, amount: int}>  $allocations
     */
    public function makePayment(Supplier $supplier, array $data, array $allocations = []): SupplierPayment
    {
        return DB::transaction(function () use ($supplier, $data, $allocations): SupplierPayment {
            // Calculate TDS/VDS if not provided
            $grossAmount = (int) $data['gross_amount'];
            $tdsAmount = (int) ($data['tds_amount'] ?? (int) round($grossAmount * ($supplier->tds_rate / 100)));
            $vdsAmount = (int) ($data['vds_amount'] ?? (int) round($grossAmount * ($supplier->vds_rate / 100)));

            $payment = SupplierPayment::withoutGlobalScopes()->create(array_merge($data, [
                'company_id' => $supplier->company_id,
                'supplier_id' => $supplier->id,
                'tds_amount' => $tdsAmount,
                'vds_amount' => $vdsAmount,
                'created_by' => auth()->id(),
            ]));

            foreach ($allocations as $alloc) {
                SupplierPaymentAllocation::withoutGlobalScopes()->create([
                    'supplier_payment_id' => $payment->id,
                    'company_id' => $supplier->company_id,
                    'purchase_order_id' => $alloc['purchase_order_id'],
                    'allocated_amount' => $alloc['amount'],
                ]);
            }

            // Record supplier transaction (debit — we paid them, reduces our payable)
            $this->contactService->recordSupplierTransaction(
                $supplier,
                'payment',
                -$payment->fresh()->net_amount,
                "Payment {$payment->payment_number}",
                $payment,
            );

            return $payment;
        });
    }

    // ═══════════════════════════════════════════════
    // Credit Notes
    // ═══════════════════════════════════════════════

    /**
     * Issue a credit note against an invoice.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array{description: string, quantity: float, unit_price: int, tax_rate?: float, product_id?: int}>  $items
     */
    public function issueCreditNote(Invoice $invoice, array $data, array $items): CreditNote
    {
        return DB::transaction(function () use ($invoice, $data, $items): CreditNote {
            $subtotal = 0;
            $taxTotal = 0;

            $creditNote = CreditNote::withoutGlobalScopes()->create(array_merge($data, [
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'status' => CreditNoteStatus::ISSUED,
                'created_by' => auth()->id(),
            ]));

            foreach ($items as $item) {
                $unitPrice = (int) $item['unit_price'];
                $qty = (float) $item['quantity'];
                $taxRate = (float) ($item['tax_rate'] ?? 0);

                $lineGross = (int) round($unitPrice * $qty);
                $lineTax = (int) round($lineGross * ($taxRate / 100));

                CreditNoteItem::withoutGlobalScopes()->create([
                    'credit_note_id' => $creditNote->id,
                    'company_id' => $invoice->company_id,
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'line_total' => $lineGross + $lineTax,
                ]);

                $subtotal += $lineGross;
                $taxTotal += $lineTax;
            }

            $creditNote->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total_amount' => $subtotal + $taxTotal,
            ]);

            return $creditNote->load('items');
        });
    }

    /**
     * Apply a credit note to reduce an invoice's balance.
     */
    public function applyCreditNote(CreditNote $creditNote, Invoice $invoice): void
    {
        DB::transaction(function () use ($creditNote, $invoice): void {
            $invoice->update([
                'amount_paid' => $invoice->amount_paid + $creditNote->total_amount,
            ]);

            // Recalculate status
            if ($invoice->amount_paid >= $invoice->total_amount) {
                $invoice->update(['status' => InvoiceStatus::PAID]);
            } elseif ($invoice->amount_paid > 0) {
                $invoice->update(['status' => InvoiceStatus::PARTIAL]);
            }

            $creditNote->update(['status' => CreditNoteStatus::APPLIED]);

            // Record customer transaction
            $customer = Customer::withoutGlobalScopes()->findOrFail($invoice->customer_id);
            $this->contactService->recordCustomerTransaction(
                $customer,
                'credit_note',
                -$creditNote->total_amount,
                "Credit Note {$creditNote->credit_note_number}",
                $creditNote,
            );
        });
    }

    // ═══════════════════════════════════════════════
    // Sales Returns
    // ═══════════════════════════════════════════════

    /**
     * Create a sales return.
     *
     * @param  array<int, array{product_id?: int, variant_id?: int, description: string, quantity: float, unit_price: int}>  $items
     */
    public function createSalesReturn(Invoice $invoice, array $items, int $warehouseId): SalesReturn
    {
        return DB::transaction(function () use ($invoice, $items, $warehouseId): SalesReturn {
            $totalAmount = 0;

            $return = SalesReturn::withoutGlobalScopes()->create([
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'warehouse_id' => $warehouseId,
                'return_date' => now()->toDateString(),
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $lineTotal = (int) round($item['unit_price'] * $item['quantity']);

                SalesReturnItem::withoutGlobalScopes()->create([
                    'sales_return_id' => $return->id,
                    'company_id' => $invoice->company_id,
                    'product_id' => $item['product_id'] ?? null,
                    'variant_id' => $item['variant_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $lineTotal,
                ]);

                $totalAmount += $lineTotal;
            }

            $return->update(['total_amount' => $totalAmount]);

            return $return->load('items');
        });
    }

    /**
     * Approve a sales return — restores stock and records customer credit.
     */
    public function approveSalesReturn(SalesReturn $return): void
    {
        DB::transaction(function () use ($return): void {
            $return->load('items');
            $return->restoreStock();
            $return->update(['status' => 'approved']);

            // Record customer transaction (credit — they returned goods)
            if ($return->customer_id !== null) {
                $customer = Customer::withoutGlobalScopes()->findOrFail($return->customer_id);
                $this->contactService->recordCustomerTransaction(
                    $customer,
                    'sales_return',
                    -$return->total_amount,
                    "Sales Return {$return->return_number}",
                    $return,
                );
            }
        });
    }

    // ═══════════════════════════════════════════════
    // Purchase Returns
    // ═══════════════════════════════════════════════

    /**
     * Create a purchase return against a goods receipt.
     *
     * @param  array<int, array{product_id?: int, variant_id?: int, description: string, quantity: float, unit_cost: int}>  $items
     */
    public function createPurchaseReturn(GoodsReceipt $receipt, array $items): PurchaseReturn
    {
        return DB::transaction(function () use ($receipt, $items): PurchaseReturn {
            $totalAmount = 0;

            $return = PurchaseReturn::withoutGlobalScopes()->create([
                'company_id' => $receipt->company_id,
                'goods_receipt_id' => $receipt->id,
                'supplier_id' => $receipt->supplier_id,
                'warehouse_id' => $receipt->warehouse_id,
                'return_date' => now()->toDateString(),
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $lineTotal = (int) round($item['unit_cost'] * $item['quantity']);

                PurchaseReturnItem::withoutGlobalScopes()->create([
                    'purchase_return_id' => $return->id,
                    'company_id' => $receipt->company_id,
                    'product_id' => $item['product_id'] ?? null,
                    'variant_id' => $item['variant_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $lineTotal,
                ]);

                $totalAmount += $lineTotal;
            }

            $return->update(['total_amount' => $totalAmount]);

            return $return->load('items');
        });
    }

    /**
     * Approve a purchase return — removes stock and records supplier credit.
     */
    public function approvePurchaseReturn(PurchaseReturn $return): void
    {
        DB::transaction(function () use ($return): void {
            $return->load('items');
            $return->removeStock();
            $return->update(['status' => 'approved']);

            // Record supplier transaction (credit — they owe us back)
            if ($return->supplier_id !== null) {
                $supplier = Supplier::withoutGlobalScopes()->findOrFail($return->supplier_id);
                $this->contactService->recordSupplierTransaction(
                    $supplier,
                    'purchase_return',
                    -$return->total_amount,
                    "Purchase Return {$return->return_number}",
                    $return,
                );
            }
        });
    }
}
