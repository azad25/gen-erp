<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Generates invoice PDFs — Mushak 6.3 format for VAT-registered companies, standard otherwise.
 */
class InvoicePDFService
{
    /**
     * Generate a PDF for the given invoice and return a temporary signed URL.
     */
    public function generate(Invoice $invoice): string
    {
        $invoice->load(['items.product', 'customer']);
        $company = Company::withoutGlobalScopes()->findOrFail($invoice->company_id);

        $data = [
            'invoice' => $invoice,
            'company' => $company,
            'items' => $invoice->items,
            'customer' => $invoice->customer,
            'isMushak' => $company->vat_registered,
        ];

        $view = $company->vat_registered ? 'pdfs.invoice-mushak' : 'pdfs.invoice-standard';
        $pdf = Pdf::loadView($view, $data)->setPaper('a4');

        $path = "private/{$company->id}/invoices/{$invoice->invoice_number}.pdf";
        Storage::put($path, $pdf->output());

        return URL::temporarySignedRoute(
            'invoice.download',
            now()->addHours(24),
            ['invoice' => $invoice->id]
        );
    }
}
