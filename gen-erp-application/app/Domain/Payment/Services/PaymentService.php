<?php

namespace App\Domain\Payment\Services;

use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Models\CustomerPayment;
use App\Domain\Customer\Models\CustomerPaymentAllocation;
use App\Domain\Customer\Models\CustomerTransaction;
use App\Domain\Customer\Models\CreditNote;
use App\Domain\Customer\Models\CreditNoteItem;
use App\Domain\Customer\Models\SalesReturn;
use App\Domain\Customer\Models\SalesReturnItem;
use App\Domain\Customer\Services\ContactService;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\Inventory\Services\InventoryService;
use App\Domain\Payment\Contracts\PaymentServiceInterface;
use App\Domain\Purchase\Models\GoodsReceipt;
use App\Domain\Purchase\Models\PurchaseReturn;
use App\Domain\Purchase\Models\PurchaseReturnItem;
use App\Domain\Purchase\Models\Supplier;
use App\Domain\Purchase\Models\SupplierPayment;
use App\Domain\Purchase\Models\SupplierPaymentAllocation;
use App\Domain\System\Services\SequenceService;
use App\Events\CreditNoteApplied;
use App\Support\Enums\CreditNoteStatus;
use App\Support\Enums\InvoiceStatus;
use App\Support\Enums\StockMovementType;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Manages payment operations - customer payments, supplier payments, returns.
 */
class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private readonly SequenceService $sequenceService,
        private readonly InventoryService $inventoryService,
        private readonly ContactService $contactService
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
                throw new InvalidArgumentException('Total allocation exceeds payment amount.');
            }

            // Create payment record
            $payment = CustomerPayment::withoutGlobalScopes()->create(array_merge($paymentData, [
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id,
                'created_by' => auth()->id(),
            ]));

            // Apply allocations to invoices
            foreach ($allocations as $allocation) {
                $this->allocatePayment($payment, $allocation['invoice_id'], (int) $allocation['amount']);
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

    /**
     * Make payment to supplier.
     */
    public function makePayment(Supplier $supplier, array $paymentData): SupplierPayment
    {
        return DB::transaction(function () use ($supplier, $paymentData) {
            $grossAmount = (int) $paymentData['gross_amount'];
            $tdsAmount = (int) ($paymentData['tds_amount'] ?? (int) round($grossAmount * ($supplier->tds_rate / 100)));
            $vdsAmount = (int) ($paymentData['vds_amount'] ?? (int) round($grossAmount * ($supplier->vds_rate / 100)));

            $payment = SupplierPayment::withoutGlobalScopes()->create(array_merge($paymentData, [
                'company_id' => $supplier->company_id,
                'supplier_id' => $supplier->id,
                'tds_amount' => $tdsAmount,
                'vds_amount' => $vdsAmount,
                'created_by' => auth()->id(),
            ]));

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

    /**
     * Issue a credit note.
     */
    public function issueCreditNote(Invoice $invoice, array $data, array $items): CreditNote
    {
        return DB::transaction(function () use ($invoice, $data, $items) {
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
     * Apply credit note to invoice.
     */
    public function applyCreditNote(CreditNote $creditNote, Invoice $invoice): void
    {
        DB::transaction(function () use ($creditNote, $invoice) {
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

            // Fire event to trigger automatic journal reversal
            event(new CreditNoteApplied($creditNote, $invoice));
        });
    }

    /**
     * Create sales return.
     */
    public function createSalesReturn(Invoice $invoice, array $items, int $warehouseId): SalesReturn
    {
        return DB::transaction(function () use ($invoice, $items, $warehouseId) {
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
     * Approve sales return and restore stock.
     */
    public function approveSalesReturn(SalesReturn $return): void
    {
        DB::transaction(function () use ($return) {
            $return->load('items');
            
            foreach ($return->items as $item) {
                if ($item->product_id) {
                    $this->inventoryService->stockIn(
                        $return->warehouse_id,
                        $item->product_id,
                        $item->quantity,
                        StockMovementType::SALE_RETURN,
                        $item->variant_id,
                        null,
                        "Sales return {$return->return_number}",
                        $return
                    );
                }
            }

            $return->update([
                'status' => 'approved',
                'stock_restored' => true,
            ]);

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

    /**
     * Create purchase return.
     */
    public function createPurchaseReturn(GoodsReceipt $receipt, array $items): PurchaseReturn
    {
        return DB::transaction(function () use ($receipt, $items) {
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
     * Approve purchase return and remove stock.
     */
    public function approvePurchaseReturn(PurchaseReturn $return): void
    {
        DB::transaction(function () use ($return) {
            $return->load('items');
            
            foreach ($return->items as $item) {
                if ($item->product_id) {
                    $this->inventoryService->stockOut(
                        $return->warehouse_id,
                        $item->product_id,
                        $item->quantity,
                        StockMovementType::PURCHASE_RETURN,
                        $item->variant_id,
                        "Purchase return {$return->return_number}",
                        $return
                    );
                }
            }

            $return->update([
                'status' => 'approved',
                'stock_removed' => true,
            ]);

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