<?php

namespace App\Domain\Compliance\Services;

use App\Domain\Auth\Models\Company;
use App\Domain\Invoice\Models\Invoice;
use Illuminate\Support\Collection;

/**
 * Mushak 6.3 PDF generation for VAT invoices.
 *
 * The Mushak 6.3 form is mandated by the NBR (National Board of Revenue)
 * of Bangladesh for VAT-registered businesses. It must accompany every
 * sale invoice when VAT is charged.
 *
 * Required fields per NBR specification:
 *   - Seller BIN, name, address
 *   - Buyer BIN (if registered), name, address
 *   - Invoice number, date
 *   - Line items with HS code, description, quantity, unit price, value
 *   - VAT rate, VAT amount per line
 *   - Total value, total VAT, grand total
 *
 * // TODO: Phase 3 — implement actual PDF rendering via DomPDF or TCPDF
 */
class Mushak63Service
{
    /**
     * Generate Mushak 6.3 data for a given invoice.
     *
     * @return array<string, mixed> The structured data for PDF rendering
     */
    public function generateData(Invoice $invoice): array
    {
        $invoice->load(['items', 'customer']);
        $company = Company::withoutGlobalScopes()->find($invoice->company_id);

        return [
            'form_type' => 'mushak_6_3',
            'seller' => [
                'bin' => $company->vat_bin,
                'name' => $company->name,
                'address' => implode(', ', array_filter([
                    $company->address_line1,
                    $company->address_line2,
                    $company->city,
                    $company->state,
                ])),
            ],
            'buyer' => [
                'name' => $invoice->customer->name ?? '',
                'address' => $invoice->customer->address ?? '',
                'bin' => $invoice->customer->vat_bin ?? null,
            ],
            'invoice' => [
                'number' => $invoice->invoice_number,
                'date' => $invoice->invoice_date?->format('d/m/Y'),
            ],
            'items' => $invoice->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit' => $item->unit ?? 'pcs',
                'unit_price' => $item->unit_price,
                'value' => $item->line_total,
                'vat_rate' => $invoice->subtotal > 0
                    ? round(($invoice->tax_amount / $invoice->subtotal) * 100, 2)
                    : 0,
                'vat_amount' => $invoice->subtotal > 0
                    ? (int) round($item->line_total * ($invoice->tax_amount / $invoice->subtotal))
                    : 0,
            ])->toArray(),
            'totals' => [
                'subtotal' => $invoice->subtotal,
                'vat_amount' => $invoice->tax_amount,
                'discount' => $invoice->discount_amount,
                'grand_total' => $invoice->total_amount,
            ],
            'currency' => 'BDT',
        ];
    }

    /**
     * Generate a Mushak 6.3 PDF for the given invoice.
     *
     * // TODO: Phase 3 — Implement PDF rendering
     *
     * @return string The file path to the generated PDF
     */
    public function generatePdf(Invoice $invoice): string
    {
        $data = $this->generateData($invoice);

        // TODO: Phase 3 — Render PDF using Blade view + DomPDF
        // return Pdf::loadView('compliance.mushak_6_3', $data)->save(...);

        throw new \RuntimeException(__('Mushak 6.3 PDF generation not yet implemented. Use generateData() for raw data.'));
    }
}
