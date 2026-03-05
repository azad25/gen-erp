<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Models\CustomerPayment;
use App\Domain\Customer\Models\CreditNote;
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
     * Receive a payment from a customer and allocate against invoices.
     *
     * @param  array<string, mixed>  $paymentData
     * @param  array<int, array{invoice_id: int, amount: int}>  $allocations
     */
    public function receivePayment(Customer $customer, array $paymentData, array $allocations = []): CustomerPayment;

    /**
     * Allocate a payment amount to a specific invoice.
     */
    public function allocatePayment(CustomerPayment $payment, int $invoiceId, int $amount): void;

    /**
     * Make a payment to a supplier with TDS/VDS deductions.
     *
     * @param  array<string, mixed>  $paymentData
     */
    public function makePayment(Supplier $supplier, array $paymentData): SupplierPayment;

    /**
     * Issue a credit note against an invoice.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array{description: string, quantity: float, unit_price: int}>  $items
     */
    public function issueCreditNote(Invoice $invoice, array $data, array $items): CreditNote;

    /**
     * Apply a credit note to reduce an invoice's balance.
     */
    public function applyCreditNote(CreditNote $creditNote, Invoice $invoice): void;

    /**
     * Create a sales return.
     *
     * @param  array<int, array{product_id?: int, variant_id?: int, description: string, quantity: float, unit_price: int}>  $items
     */
    public function createSalesReturn(Invoice $invoice, array $items, int $warehouseId): SalesReturn;

    /**
     * Approve a sales return — restores stock and records customer credit.
     */
    public function approveSalesReturn(SalesReturn $return): void;

    /**
     * Create a purchase return against a goods receipt.
     *
     * @param  array<int, array{product_id?: int, variant_id?: int, description: string, quantity: float, unit_cost: int}>  $items
     */
    public function createPurchaseReturn(GoodsReceipt $receipt, array $items): PurchaseReturn;

    /**
     * Approve a purchase return — removes stock and records supplier credit.
     */
    public function approvePurchaseReturn(PurchaseReturn $return): void;
}
