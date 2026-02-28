<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mushak 6.3 — {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 20px; }
        .mushak-header { text-align: center; margin-bottom: 15px; border-bottom: 2px double #333; padding-bottom: 10px; }
        .mushak-header h2 { margin: 0; font-size: 16px; }
        .mushak-header h3 { margin: 5px 0; font-size: 13px; color: #555; }
        .mushak-ref { font-size: 10px; color: #888; }
        .parties { margin-bottom: 15px; }
        .parties table { width: 100%; }
        .parties td { vertical-align: top; padding: 5px; }
        .party-box { border: 1px solid #ccc; padding: 8px; font-size: 10px; }
        .party-label { font-weight: bold; text-transform: uppercase; font-size: 9px; color: #666; margin-bottom: 4px; }
        .bin { font-weight: bold; color: #1a56db; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.items th { background: #2c3e50; color: white; padding: 6px; text-align: left; font-size: 10px; border: 1px solid #2c3e50; }
        table.items td { padding: 5px; border: 1px solid #ddd; font-size: 10px; }
        table.items tr:nth-child(even) { background: #f8f9fa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals-section { width: 320px; float: right; }
        .totals-section table { width: 100%; }
        .totals-section td { padding: 4px 8px; font-size: 11px; border-bottom: 1px solid #eee; }
        .grand-total td { font-weight: bold; font-size: 13px; background: #2c3e50; color: white; border: none; }
        .footer-note { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 9px; color: #888; text-align: center; }
        .invoice-meta { margin-bottom: 10px; }
        .invoice-meta td { padding: 3px 8px; font-size: 11px; }
    </style>
</head>
<body>
    <div class="mushak-header">
        <h2>{{ __('মূসক-৬.৩') }} / Mushak 6.3</h2>
        <h3>{{ __('Tax Invoice / কর চালানপত্র') }}</h3>
        <div class="mushak-ref">[{{ __('See rule 40(1), clause (ga)') }}]</div>
    </div>

    <table class="invoice-meta">
        <tr>
            <td><strong>{{ __('Invoice No') }}:</strong> {{ $invoice->invoice_number }}</td>
            <td><strong>{{ __('Mushak No') }}:</strong> {{ $invoice->mushak_number ?? 'N/A' }}</td>
            <td class="text-right"><strong>{{ __('Date') }}:</strong> {{ $invoice->invoice_date->format('d M Y') }}</td>
        </tr>
    </table>

    <div class="parties">
        <table>
            <tr>
                <td width="48%">
                    <div class="party-box">
                        <div class="party-label">{{ __('Seller / বিক্রেতা') }}</div>
                        <div><strong>{{ $company->name }}</strong></div>
                        <div>{{ $company->address ?? '' }}</div>
                        <div class="bin">{{ __('BIN') }}: {{ $company->vat_bin ?? 'N/A' }}</div>
                    </div>
                </td>
                <td width="4%"></td>
                <td width="48%">
                    <div class="party-box">
                        <div class="party-label">{{ __('Buyer / ক্রেতা') }}</div>
                        @if($customer)
                        <div><strong>{{ $customer->name }}</strong></div>
                        <div>{{ $customer->address_line1 ?? '' }}</div>
                        <div class="bin">{{ __('BIN') }}: {{ $customer->vat_bin ?? 'N/A' }}</div>
                        @else
                        <div>{{ __('Walk-in Customer') }}</div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>{{ __('Description') }}</th>
                <th class="text-right">{{ __('Qty') }}</th>
                <th>{{ __('Unit') }}</th>
                <th class="text-right">{{ __('Unit Price') }}</th>
                <th class="text-right">{{ __('Taxable Value') }}</th>
                <th class="text-center">{{ __('VAT %') }}</th>
                <th class="text-right">{{ __('VAT Amount') }}</th>
                <th class="text-right">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $i => $item)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                <td>{{ $item->unit }}</td>
                <td class="text-right">৳{{ number_format($item->unit_price / 100, 2) }}</td>
                <td class="text-right">৳{{ number_format(($item->line_total - $item->tax_amount) / 100, 2) }}</td>
                <td class="text-center">{{ number_format($item->tax_rate, 1) }}%</td>
                <td class="text-right">৳{{ number_format($item->tax_amount / 100, 2) }}</td>
                <td class="text-right">৳{{ number_format($item->line_total / 100, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        <table>
            <tr><td>{{ __('Taxable Value') }}</td><td class="text-right">৳{{ number_format(($invoice->subtotal - $invoice->discount_amount) / 100, 2) }}</td></tr>
            <tr><td>{{ __('Total VAT') }}</td><td class="text-right">৳{{ number_format($invoice->tax_amount / 100, 2) }}</td></tr>
            @if($invoice->discount_amount > 0)
            <tr><td>{{ __('Discount') }}</td><td class="text-right">-৳{{ number_format($invoice->discount_amount / 100, 2) }}</td></tr>
            @endif
            @if($invoice->shipping_amount > 0)
            <tr><td>{{ __('Shipping') }}</td><td class="text-right">৳{{ number_format($invoice->shipping_amount / 100, 2) }}</td></tr>
            @endif
            <tr class="grand-total"><td>{{ __('Grand Total') }}</td><td class="text-right">৳{{ number_format($invoice->total_amount / 100, 2) }}</td></tr>
        </table>
    </div>

    <div style="clear:both;"></div>

    <div class="footer-note">
        {{ __('Generated under the Value Added Tax and Supplementary Duty Act 2012') }}
    </div>
</body>
</html>
