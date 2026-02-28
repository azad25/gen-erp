<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 11px; width: 100%; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .company-name { font-size: 14px; font-weight: bold; }
        .receipt-title { font-size: 10px; margin: 2px 0 4px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1px 0; vertical-align: top; }
        .col-qty { width: 30px; }
        .col-price { width: 60px; text-align: right; }
        .col-total { width: 60px; text-align: right; }
        .totals td { padding: 2px 0; }
        .footer { margin-top: 8px; font-size: 9px; }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="center">
        <div class="company-name">{{ $company_name }}</div>
        @if($branch_name)
            <div>{{ $branch_name }}</div>
        @endif
        @if($branch_address)
            <div style="font-size:9px">{{ $branch_address }}</div>
        @endif
        @if($company_phone)
            <div style="font-size:9px">{{ __('Tel') }}: {{ $company_phone }}</div>
        @endif
        @if($company_vat_bin)
            <div style="font-size:9px">{{ __('BIN') }}: {{ $company_vat_bin }}</div>
        @endif
    </div>

    <div class="divider"></div>

    {{-- Receipt Info --}}
    <div>
        <div><strong>{{ __('Receipt') }}:</strong> {{ $sale_number }}</div>
        <div><strong>{{ __('Date') }}:</strong> {{ $sale_date }}</div>
        @if($cashier)
            <div><strong>{{ __('Cashier') }}:</strong> {{ $cashier }}</div>
        @endif
        @if($customer_name)
            <div><strong>{{ __('Customer') }}:</strong> {{ $customer_name }}</div>
        @endif
    </div>

    <div class="divider"></div>

    {{-- Items --}}
    <table>
        <thead>
            <tr>
                <td class="bold">{{ __('Item') }}</td>
                <td class="bold col-qty">{{ __('Qty') }}</td>
                <td class="bold col-price">{{ __('Price') }}</td>
                <td class="bold col-total">{{ __('Total') }}</td>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item['description'] }}</td>
                <td class="col-qty">{{ $item['quantity'] }}</td>
                <td class="col-price">{{ number_format($item['unit_price'], 2) }}</td>
                <td class="col-total">{{ number_format($item['line_total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    {{-- Totals --}}
    <table class="totals">
        <tr>
            <td>{{ __('Subtotal') }}</td>
            <td class="right">৳{{ number_format($subtotal, 2) }}</td>
        </tr>
        @if($discount > 0)
        <tr>
            <td>{{ __('Discount') }}</td>
            <td class="right">-৳{{ number_format($discount, 2) }}</td>
        </tr>
        @endif
        @if($tax > 0)
        <tr>
            <td>{{ __('VAT') }}</td>
            <td class="right">৳{{ number_format($tax, 2) }}</td>
        </tr>
        @endif
        <tr class="bold">
            <td style="font-size:13px">{{ __('TOTAL') }}</td>
            <td class="right" style="font-size:13px">৳{{ number_format($total, 2) }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    {{-- Payment --}}
    <table>
        <tr>
            <td>{{ __('Payment') }}</td>
            <td class="right">{{ ucfirst($payment_method) }}</td>
        </tr>
        @if($payment_method === 'cash')
        <tr>
            <td>{{ __('Tendered') }}</td>
            <td class="right">৳{{ number_format($amount_tendered, 2) }}</td>
        </tr>
        <tr class="bold">
            <td>{{ __('Change') }}</td>
            <td class="right">৳{{ number_format($change, 2) }}</td>
        </tr>
        @endif
    </table>

    <div class="divider"></div>

    {{-- Footer --}}
    <div class="center footer">
        <div>{{ __('Thank you for your purchase!') }}</div>
        <div>{{ __('ধন্যবাদ') }}</div>
        <div style="margin-top:4px">{{ __('Powered by GenERP BD') }}</div>
    </div>
</body>
</html>
