<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PO {{ $order->reference_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 30px; }
        .company-name { font-size: 20px; font-weight: bold; }
        .po-title { font-size: 24px; font-weight: bold; text-align: right; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #2c3e50; color: white; padding: 8px; text-align: left; font-size: 11px; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #f8f9fa; }
        .text-right { text-align: right; }
        .totals { float: right; width: 280px; }
        .totals td { border-bottom: 1px solid #ddd; }
        .grand-total { font-weight: bold; font-size: 14px; background: #2c3e50 !important; color: white; }
        .grand-total td { border: none; }
        .terms { margin-top: 40px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 11px; color: #666; }
        .footer-note { margin-top: 30px; text-align: center; font-size: 10px; color: #aaa; }
    </style>
</head>
<body>
    <table width="100%">
        <tr>
            <td width="60%">
                <div class="company-name">{{ $company->name }}</div>
                <div>{{ $company->address ?? '' }}</div>
                <div>{{ $company->phone ?? '' }}</div>
                @if($company->vat_bin)<div><strong>{{ __('VAT BIN') }}:</strong> {{ $company->vat_bin }}</div>@endif
            </td>
            <td width="40%" class="text-right">
                <div class="po-title">{{ __('PURCHASE ORDER') }}</div>
                <div><strong>{{ __('PO #') }}:</strong> {{ $order->reference_number }}</div>
                <div><strong>{{ __('Date') }}:</strong> {{ $order->order_date->format('d M Y') }}</div>
                @if($order->expected_delivery_date)
                <div><strong>{{ __('Expected') }}:</strong> {{ $order->expected_delivery_date->format('d M Y') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <br>

    @if($supplier)
    <table width="100%">
        <tr>
            <td width="50%" style="background: #f8f9fa; padding: 12px;">
                <div style="font-weight: bold; font-size: 11px; color: #666; text-transform: uppercase; margin-bottom: 4px;">{{ __('Supplier') }}</div>
                <div><strong>{{ $supplier->name }}</strong></div>
                <div>{{ $supplier->address_line1 ?? '' }}</div>
                <div>{{ $supplier->city ?? '' }}{{ $supplier->district ? ', ' . $supplier->district : '' }}</div>
                <div>{{ $supplier->phone ?? '' }}</div>
                @if($supplier->vat_bin)<div><strong>{{ __('BIN') }}:</strong> {{ $supplier->vat_bin }}</div>@endif
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
                <th class="text-right">{{ __('Unit Cost') }}</th>
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
                <td class="text-right">{{ number_format($item->quantity_ordered, 2) }}</td>
                <td>{{ $item->unit }}</td>
                <td class="text-right">৳{{ number_format($item->unit_cost / 100, 2) }}</td>
                <td class="text-right">{{ $item->discount_percent > 0 ? $item->discount_percent . '%' : '-' }}</td>
                <td class="text-right">৳{{ number_format($item->tax_amount / 100, 2) }}</td>
                <td class="text-right">৳{{ number_format($item->line_total / 100, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr><td>{{ __('Subtotal') }}</td><td class="text-right">৳{{ number_format($order->subtotal / 100, 2) }}</td></tr>
            @if($order->discount_amount > 0)
            <tr><td>{{ __('Discount') }}</td><td class="text-right">-৳{{ number_format($order->discount_amount / 100, 2) }}</td></tr>
            @endif
            @if($order->tax_amount > 0)
            <tr><td>{{ __('Tax') }}</td><td class="text-right">৳{{ number_format($order->tax_amount / 100, 2) }}</td></tr>
            @endif
            @if($order->shipping_amount > 0)
            <tr><td>{{ __('Shipping') }}</td><td class="text-right">৳{{ number_format($order->shipping_amount / 100, 2) }}</td></tr>
            @endif
            <tr class="grand-total"><td>{{ __('Grand Total') }}</td><td class="text-right">৳{{ number_format($order->total_amount / 100, 2) }}</td></tr>
        </table>
    </div>

    <div style="clear:both;"></div>

    @if($order->terms_conditions)
    <div class="terms">
        <strong>{{ __('Terms & Conditions') }}</strong>
        <p>{{ $order->terms_conditions }}</p>
    </div>
    @endif

    <div class="footer-note">{{ __('This is a computer generated document') }}</div>
</body>
</html>
