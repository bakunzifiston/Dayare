<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $sale->sale_number }} — {{ __('Invoice') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; }
        .header { border-bottom: 2px solid #7f1d1d; padding-bottom: 12px; margin-bottom: 18px; }
        .brand { font-size: 20px; font-weight: bold; color: #7f1d1d; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px; }
        .meta td { border: none; padding: 4px 0; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">{{ $business->business_name ?: config('app.name') }}</div>
        <div>{{ __('Tax invoice') }}</div>
    </div>
    <table class="meta">
        <tr><td><strong>{{ __('Invoice #') }}</strong> {{ $sale->sale_number }}</td><td class="right"><strong>{{ __('Date') }}</strong> {{ $sale->sale_date?->toDateString() }}</td></tr>
        <tr><td><strong>{{ __('Bill to') }}</strong> {{ $sale->customer?->name ?? __('Walk-in') }}</td><td class="right"><strong>{{ __('Outlet') }}</strong> {{ $sale->outlet?->name }}</td></tr>
    </table>
    <table>
        <thead>
            <tr>
                <th>{{ __('Description') }}</th>
                <th>{{ __('Qty') }}</th>
                <th class="right">{{ __('Unit price') }}</th>
                <th class="right">{{ __('Subtotal') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->product?->name }}</td>
                    <td>
                        @if ((float) $item->quantity_kg > 0){{ number_format((float) $item->quantity_kg, 2) }} kg @endif
                        @if ($item->quantity_units){{ $item->quantity_units }} @endif
                    </td>
                    <td class="right">{{ number_format((float) $item->unit_price, 0) }}</td>
                    <td class="right">{{ number_format((float) $item->subtotal, 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table class="meta" style="margin-top:16px;width:40%;margin-left:auto;">
        <tr><td>{{ __('Subtotal') }}</td><td class="right">{{ number_format((float) $sale->subtotal, 0) }} RWF</td></tr>
        <tr><td>{{ __('Discount') }}</td><td class="right">{{ number_format((float) $sale->discount_amount, 0) }} RWF</td></tr>
        <tr><td><strong>{{ __('Total due') }}</strong></td><td class="right"><strong>{{ number_format((float) $sale->total_amount, 0) }} RWF</strong></td></tr>
    </table>
</body>
</html>
