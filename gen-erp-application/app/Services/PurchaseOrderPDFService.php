<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Generates purchase order PDFs.
 */
class PurchaseOrderPDFService
{
    /**
     * Generate a PDF for the given PO and return a temporary signed URL.
     */
    public function generate(PurchaseOrder $order): string
    {
        $order->load(['items.product', 'supplier']);
        $company = Company::withoutGlobalScopes()->findOrFail($order->company_id);

        $data = [
            'order' => $order,
            'company' => $company,
            'items' => $order->items,
            'supplier' => $order->supplier,
        ];

        $pdf = Pdf::loadView('pdfs.purchase-order', $data)->setPaper('a4');

        $path = "private/{$company->id}/purchase-orders/{$order->reference_number}.pdf";
        Storage::put($path, $pdf->output());

        return URL::temporarySignedRoute(
            'purchase-order.download',
            now()->addHours(24),
            ['purchaseOrder' => $order->id]
        );
    }
}
