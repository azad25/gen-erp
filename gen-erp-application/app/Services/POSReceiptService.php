<?php

namespace App\Services;

use App\Models\POSSale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

/**
 * POS receipt builder — generates receipt data and PDF for thermal printing.
 */
class POSReceiptService
{
    /**
     * Build receipt data for a POS sale.
     *
     * @return array<string, mixed>
     */
    public function buildReceiptData(POSSale $sale): array
    {
        $sale->load(['items.product', 'session', 'branch', 'customer']);

        $company = $sale->company;

        return [
            'company_name' => $company?->name ?? 'GenERP BD',
            'company_address' => $company?->address ?? '',
            'company_phone' => $company?->phone ?? '',
            'company_vat_bin' => $company?->vat_bin ?? '',
            'branch_name' => $sale->branch?->name,
            'branch_address' => $sale->branch?->address,
            'sale_number' => $sale->sale_number,
            'sale_date' => $sale->sale_date?->format('d M Y H:i'),
            'cashier' => $sale->session?->user?->name ?? '',
            'items' => $sale->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price / 100,
                'line_total' => $item->line_total / 100,
            ])->toArray(),
            'subtotal' => $sale->subtotal / 100,
            'discount' => ($sale->discount_amount ?? 0) / 100,
            'tax' => ($sale->tax_amount ?? 0) / 100,
            'total' => $sale->total_amount / 100,
            'payment_method' => $sale->payment_method ?? 'cash',
            'amount_tendered' => ($sale->amount_tendered ?? 0) / 100,
            'change' => ($sale->change_amount ?? 0) / 100,
            'customer_name' => $sale->customer?->name,
        ];
    }

    /** Generate PDF receipt for download or print. */
    public function generatePdf(POSSale $sale, string $size = '80mm'): \Barryvdh\DomPDF\PDF
    {
        $data = $this->buildReceiptData($sale);

        $width = $size === '58mm' ? 219 : 302;

        $pdf = Pdf::loadView('pdfs.pos-receipt', $data);
        $pdf->setPaper([0, 0, $width, 800], 'portrait');

        return $pdf;
    }

    /** Save receipt PDF to storage and return path. */
    public function savePdf(POSSale $sale): string
    {
        $pdf = $this->generatePdf($sale);
        $path = "receipts/{$sale->company_id}/{$sale->sale_number}.pdf";

        $fullPath = storage_path("app/private/{$path}");
        $dir = dirname($fullPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdf->save($fullPath);

        return $path;
    }
}
