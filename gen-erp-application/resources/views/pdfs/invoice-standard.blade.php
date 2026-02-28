<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 30px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .company-name { font-size: 20px; font-weight: bold; }
        .invoice-title { font-size: 24px; font-weight: bold; text-align: right; color: #1a56db; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .info-box { background: #f8f9fa; padding: 12px; border-radius: 4px; }
        .info-label { font-weight: bold; font-size: 11px; color: #666; text-transform: uppercase; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #1a56db; color: white; padding: 8px; text-align: left; font-size: 11px; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #f8f9fa; }
        .totals { float: right; width: 280px; }
        .totals table { margin-top: 10px; }
        .totals td { border-bottom: 1px solid #ddd; }
        .grand-total { font-weight: bold; font-size: 14px; background: #1a56db !important; color: white; }
        .grand-total td { border: none; }
        .text-right { text-align: right; }
        .terms { margin-top: 40px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 11px; color: #666; }
    </style>
</head>
<body>
    <table width="100%">
        <tr>
            <td width="60%">
                <div class="company-name">{{ $company->name }}</div>
                <div>{{ $company->address ?? '' }}</div>
                <div>{{ $company->phone ?? '' }}</div>
                <div>{{ $company->email ?? '' }}</div>
            </td>
            <td width="40%" class="text-right">
                <div class="invoice-title">{{ __('INVOICE') }}</div>
                <div><strong>{{ __('Invoice #') }}:</strong> {{ $invoice->invoice_number }}</div>
                <div><strong>{{ __('Date') }}:</strong> {{ $invoice->invoice_date->format('d M Y') }}</div>
                <div><strong>{{ __('Due Date') }}:</strong> {{ $invoice->due_date->format('d M Y') }}</div>
            </td>
        </tr>
    </table>

    <br>

    @if($customer)
    <table width="100%">
        <tr>
            <td width="50%" style="background: #f8f9fa; padding: 12px;">
                <div style="font-weight: bold; font-size: 11px; color: #666; text-transform: uppercase; margin-bottom: 4px;">{{ __('Bill To') }}</div>
                <div><strong>{{ $customer->name }}</strong></div>
                <div>{{ $customer->address_line1 ?? '' }}</div>
                <div>{{ $customer->city ?? '' }}{{ $customer->district ? ', ' . $customer->district : '' }}</div>
                <div>{{ $customer->phone ?? '' }}</div>
            </td>
            <td width="50%"></td>
        </tr>
    </table>
    @endif

    <br>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('Description') }}</th>
                <th class="text-right">{{ __('Qty') }}</th>
                <th>{{ __('Unit') }}</th>
                <th class="text-right">{{ __('Price') }}</th>
                <th class="text-right">{{ __('Discount') }}</th>
                <th class="text-right">{{ __('Tax') }}</th>
                <th class="text-right">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                <td>{{ $item->unit }}</td>
                <td class="text-right">৳{{ number_format($item->unit_price / 100, 2) }}</td>
                <td class="text-right">{{ $item->discount_percent > 0 ? $item->discount_percent . '%' : '-' }}</td>
                <td class="text-right">৳{{ number_format($item->tax_amount / 100, 2) }}</td>
                <td class="text-right">৳{{ number_format($item->line_total / 100, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr><td>{{ __('Subtotal') }}</td><td class="text-right">৳{{ number_format($invoice->subtotal / 100, 2) }}</td></tr>
            @if($invoice->discount_amount > 0)
            <tr><td>{{ __('Discount') }}</td><td class="text-right">-৳{{ number_format($invoice->discount_amount / 100, 2) }}</td></tr>
            @endif
            @if($invoice->tax_amount > 0)
            <tr><td>{{ __('VAT/Tax') }}</td><td class="text-right">৳{{ number_format($invoice->tax_amount / 100, 2) }}</td></tr>
            @endif
            @if($invoice->shipping_amount > 0)
            <tr><td>{{ __('Shipping') }}</td><td class="text-right">৳{{ number_format($invoice->shipping_amount / 100, 2) }}</td></tr>
            @endif
            <tr class="grand-total"><td>{{ __('Grand Total') }}</td><td class="text-right">৳{{ number_format($invoice->total_amount / 100, 2) }}</td></tr>
            @if($invoice->amount_paid > 0)
            <tr><td>{{ __('Paid') }}</td><td class="text-right">৳{{ number_format($invoice->amount_paid / 100, 2) }}</td></tr>
            <tr><td><strong>{{ __('Balance Due') }}</strong></td><td class="text-right"><strong>৳{{ number_format(($invoice->total_amount - $invoice->amount_paid) / 100, 2) }}</strong></td></tr>
            @endif
        </table>
    </div>

    <div style="clear:both;"></div>

    @if($invoice->terms_conditions)
    <div class="terms">
        <strong>{{ __('Terms & Conditions') }}</strong>
        <p>{{ $invoice->terms_conditions }}</p>
    </div>
    @endif
</body>
</html>
