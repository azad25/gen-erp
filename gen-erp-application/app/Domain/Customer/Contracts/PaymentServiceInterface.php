<?php

namespace App\Domain\Customer\Contracts;

use App\Domain\Customer\Models\CreditNote;
use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Models\CustomerPayment;
use App\Domain\Customer\Models\SalesReturn;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\Purchase\Models\GoodsReceipt;
use App\Domain\Purchase\Models\PurchaseReturn;
use App\Domain\Purchase\Models\Supplier;
use App\Domain\Purchase\Models\SupplierPayment;

/**
 * Interface for payment service operations.
 */
interface PaymentServiceInterface
{
    /**
     * Receive customer payment.
     */
    public function receivePayment(Customer $customer, array $data, array $allocations = []): CustomerPayment;

    /**
     * Allocate payment to invoice.
     */
    public function allocatePayment(CustomerPayment $payment, int $invoiceId, int $amount): void;

    /**
     * Make supplier payment.
     */
    public function makePayment(Supplier $supplier, array $data, array $allocations = []): SupplierPayment;

    /**
     * Issue credit note.
     */
    public function issueCreditNote(Invoice $invoice, array $data, array $items): CreditNote;

    /**
     * Apply credit note to invoice.
     */
    public function applyCreditNote(CreditNote $creditNote, Invoice $invoice): void;

    /**
     * Create sales return.
     */
    public function createSalesReturn(Invoice $invoice, array $items, int $warehouseId): SalesReturn;

    /**
     * Approve sales return.
     */
    public function approveSalesReturn(SalesReturn $return): void;

    /**
     * Create purchase return.
     */
    public function createPurchaseReturn(GoodsReceipt $receipt, array $items): PurchaseReturn;

    /**
     * Approve purchase return.
     */
    public function approvePurchaseReturn(PurchaseReturn $return): void;
}