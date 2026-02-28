<?php

namespace App\Services;

/**
 * POS receipt data builder — actual PDF rendering deferred to Phase 7.
 */
class POSReceiptService
{
    /**
     * Build receipt data for a POS sale.
     *
     * @return array<string, mixed>
     */
    public function buildReceiptData(\App\Models\POSSale $sale): array
    {
        $sale->load(['items.product', 'session', 'branch', 'customer']);

        return [
            'company_name' => $sale->branch?->name ?? 'GenERP',
            'branch_name' => $sale->branch?->name,
            'branch_address' => $sale->branch?->address,
            'sale_number' => $sale->sale_number,
            'sale_date' => $sale->sale_date?->format('d M Y H:i'),
            'items' => $sale->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ])->toArray(),
            'subtotal' => $sale->subtotal,
            'discount' => $sale->discount_amount,
            'tax' => $sale->tax_amount,
            'total' => $sale->total_amount,
            'amount_tendered' => $sale->amount_tendered,
            'change' => $sale->change_amount,
            'customer_name' => $sale->customer?->name,
        ];
    }
}
