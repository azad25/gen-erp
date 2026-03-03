<?php

namespace App\Domain\Invoice\Services;

use App\Support\Enums\InvoiceStatus;
use App\Support\Enums\StockMovementType;
use App\Domain\Auth\Models\Company;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\Invoice\Models\InvoiceItem;
use App\Domain\Product\Models\Product;
use App\Domain\SalesOrder\Models\SalesOrder;
use App\Domain\Invoice\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Domain\Invoice\DTOs\CreateInvoiceData;
use App\Domain\Invoice\Actions\SendInvoiceAction;
use App\Domain\Invoice\Actions\CancelInvoiceAction;
use App\Domain\Invoice\Actions\ConvertOrderToInvoiceAction;
use App\Domain\Customer\Services\ContactService;
use App\Domain\Inventory\Services\InventoryService;
use Illuminate\Support\Facades\DB;

/**
 * Handles all invoice-related operations.
 */
class InvoiceService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly ContactService $contactService,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly SendInvoiceAction $sendInvoiceAction,
        private readonly CancelInvoiceAction $cancelInvoiceAction,
        private readonly ConvertOrderToInvoiceAction $convertOrderToInvoiceAction,
    ) {}

    /**
     * Paginated invoice listing with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateInvoices(Company $company, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->invoiceRepository->paginateForCompany($company, $filters, $perPage);
    }

    /**
     * Convert a sales order into an invoice.
     */
    public function convertToInvoice(SalesOrder $order): Invoice
    {
        return $this->convertOrderToInvoiceAction->execute($order);
    }

    /**
     * Create a direct invoice (not from a sales order).
     */
    public function createInvoice(Company $company, array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($company, $data, $items): Invoice {
            $totals = $this->calculateTotals($items, $company->vat_registered);

            $creditDays = 30;
            if (isset($data['customer_id'])) {
                $customer = \App\Domain\Customer\Models\Customer::withoutGlobalScopes()->find($data['customer_id']);
                $creditDays = $customer?->credit_days ?? 30;
            }

            $invoiceDate = $data['invoice_date'] ?? now()->toDateString();
            $dueDate = $data['due_date'] ?? now()->addDays($creditDays)->toDateString();

            $invoice = Invoice::withoutGlobalScopes()->create(array_merge($data, [
                'company_id' => $company->id,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'],
                'created_by' => auth()->id(),
            ]));

            $this->saveInvoiceItems($invoice, $items, $company);

            return $invoice;
        });
    }

    /**
     * Update a draft invoice.
     */
    public function updateInvoice(Invoice $invoice, array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $items): Invoice {
            $company = Company::withoutGlobalScopes()->findOrFail($invoice->company_id);
            $totals = $this->calculateTotals($items, $company->vat_registered);

            $invoice->update(array_merge($data, [
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'],
            ]));

            $invoice->items()->delete();
            $this->saveInvoiceItems($invoice, $items, $company);

            return $invoice->fresh('items');
        });
    }

    /**
     * Send an invoice — deduct stock, record customer transaction, update status.
     */
    public function sendInvoice(Invoice $invoice): void
    {
        $this->sendInvoiceAction->execute($invoice);
    }

    /**
     * Cancel an invoice — reverse stock if already deducted.
     */
    public function cancelInvoice(Invoice $invoice): void
    {
        $this->cancelInvoiceAction->execute($invoice);
    }

    /**
     * Calculate totals for a set of line items.
     *
     * @param  array<int, array{unit_price: int, quantity: float, discount_percent?: float, tax_rate?: float}>  $items
     * @return array{subtotal: int, discount: int, tax: int, total: int}
     */
    public function calculateTotals(array $items, bool $vatRegistered = false): array
    {
        $subtotal = 0;
        $totalDiscount = 0;
        $totalTax = 0;

        foreach ($items as $item) {
            $unitPrice = (int) ($item['unit_price'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);
            $discountPercent = (float) ($item['discount_percent'] ?? 0);
            $taxRate = (float) ($item['tax_rate'] ?? 0);

            $lineGross = (int) round($unitPrice * $quantity);
            $lineDiscount = (int) round($lineGross * ($discountPercent / 100));
            $lineNet = $lineGross - $lineDiscount;
            $lineTax = $vatRegistered ? (int) round($lineNet * ($taxRate / 100)) : 0;

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

    /**
     * Save invoice line items.
     */
    private function saveInvoiceItems(Invoice $invoice, array $items, Company $company): void
    {
        foreach ($items as $i => $item) {
            $unitPrice = (int) ($item['unit_price'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);
            $discountPercent = (float) ($item['discount_percent'] ?? 0);
            $taxRate = (float) ($item['tax_rate'] ?? 0);

            $lineGross = (int) round($unitPrice * $quantity);
            $lineDiscount = (int) round($lineGross * ($discountPercent / 100));
            $lineNet = $lineGross - $lineDiscount;
            $lineTax = $company->vat_registered ? (int) round($lineNet * ($taxRate / 100)) : 0;

            InvoiceItem::withoutGlobalScopes()->create([
                'invoice_id' => $invoice->id,
                'company_id' => $company->id,
                'product_id' => $item['product_id'] ?? null,
                'variant_id' => $item['variant_id'] ?? null,
                'description' => $item['description'] ?? '',
                'quantity' => $quantity,
                'unit' => $item['unit'] ?? 'pcs',
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'discount_amount' => $lineDiscount,
                'tax_group_id' => $item['tax_group_id'] ?? null,
                'tax_rate' => $taxRate,
                'tax_amount' => $lineTax,
                'line_total' => $lineNet + $lineTax,
                'display_order' => $i,
            ]);
        }
    }
}