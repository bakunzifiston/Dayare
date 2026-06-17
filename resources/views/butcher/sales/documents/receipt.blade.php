<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $sale->sale_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 11px; }
        .header { border-bottom: 2px solid #7f1d1d; padding-bottom: 10px; margin-bottom: 14px; }
        .brand { font-size: 16px; font-weight: bold; color: #7f1d1d; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e2e8f0; padding: 6px; text-align: left; }
        .totals { margin-top: 12px; width: 50%; margin-left: auto; }
        .totals td { border: none; padding: 3px 0; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">{{ $business->business_name ?: config('app.name') }}</div>
        <div>{{ __('Sales receipt') }} · {{ $sale->sale_number }}</div>
    </div>
    <p>{{ $sale->outlet?->name }} · {{ $sale->sale_date?->format('d M Y H:i') }}</p>
    <p>{{ __('Customer') }}: {{ $sale->customer?->name ?? __('Walk-in') }}</p>
    <table>
        <thead>
            <tr>
                <th>{{ __('Item') }}</th>
                <th>{{ __('Qty') }}</th>
                <th class="right">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->product?->name }}</td>
                    <td>
                        @if ((float) $item->quantity_kg > 0){{ number_format((float) $item->quantity_kg, 2) }} kg @endif
                        @if ($item->quantity_units){{ $item->quantity_units }} u @endif
                    </td>
                    <td class="right">{{ number_format((float) $item->subtotal, 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table class="totals">
        <tr><td>{{ __('Subtotal') }}</td><td class="right">{{ number_format((float) $sale->subtotal, 0) }}</td></tr>
        @if ((float) $sale->discount_amount > 0)
            <tr><td>{{ __('Discount') }}</td><td class="right">−{{ number_format((float) $sale->discount_amount, 0) }}</td></tr>
        @endif
        <tr><td><strong>{{ __('Total') }}</strong></td><td class="right"><strong>{{ number_format((float) $sale->total_amount, 0) }} RWF</strong></td></tr>
        <tr><td>{{ __('Paid') }} ({{ $sale->payment_method }})</td><td class="right">{{ number_format((float) $sale->amount_paid, 0) }}</td></tr>
        @if ((float) $sale->change_given > 0)
            <tr><td>{{ __('Change') }}</td><td class="right">{{ number_format((float) $sale->change_given, 0) }}</td></tr>
        @endif
    </table>
    <p style="margin-top:20px;font-size:9px;color:#64748b;">{{ __('Thank you for your purchase.') }}</p>
</body>
</html>
