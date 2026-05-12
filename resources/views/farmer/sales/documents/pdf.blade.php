<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $document->document_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; }
        .header { border-bottom: 2px solid #b91c1c; padding-bottom: 12px; margin-bottom: 18px; }
        .brand { font-size: 20px; font-weight: bold; color: #b91c1c; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .grid td { border: 1px solid #e2e8f0; padding: 8px; vertical-align: top; }
        .section { margin-top: 18px; }
        .section h3 { margin: 0 0 8px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em; }
        .totals { margin-top: 12px; width: 45%; margin-left: auto; }
        .totals td { border: none; padding: 4px 0; }
        .signature { margin-top: 28px; display: table; width: 100%; }
        .signature div { display: table-cell; width: 50%; padding-top: 36px; border-top: 1px solid #cbd5e1; }
        .qr { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">{{ $sale->farm?->business?->business_name ?: config('app.name') }}</div>
        <div>{{ __('Livestock sales document') }} · {{ str_replace('_', ' ', $document->document_type) }}</div>
    </div>
    <table class="grid">
        <tr><td><strong>{{ __('Document number') }}</strong><br>{{ $document->document_number }}</td><td><strong>{{ __('Sale number') }}</strong><br>{{ $sale->sale_number }}</td></tr>
        <tr><td><strong>{{ __('Buyer') }}</strong><br>{{ $sale->buyer?->buyer_name }}</td><td><strong>{{ __('Farm') }}</strong><br>{{ $sale->farm?->name }}</td></tr>
        <tr><td><strong>{{ __('Sale date') }}</strong><br>{{ $sale->sale_date?->toDateString() }}</td><td><strong>{{ __('Destination') }}</strong><br>{{ $sale->destination ?: '—' }}</td></tr>
    </table>
    <div class="section">
        <h3>{{ __('Animal summary') }}</h3>
        <table class="grid">
            <tr><td><strong>{{ __('Animal / group') }}</strong></td><td><strong>{{ __('Weight') }}</strong></td><td><strong>{{ __('Price') }}</strong></td></tr>
            @foreach ($sale->saleAnimals as $line)
                <tr>
                    <td>{{ $line->animal?->animal_code ?: ($line->livestock?->livestock_code ?: '—') }}</td>
                    <td>{{ $line->live_weight ? number_format($line->live_weight, 2).' kg' : '—' }}</td>
                    <td>{{ number_format($line->sale_price, 2) }} {{ $sale->currency }}</td>
                </tr>
            @endforeach
        </table>
    </div>
    <table class="totals">
        <tr><td>{{ __('Subtotal') }}</td><td style="text-align:right;">{{ number_format($sale->subtotal_amount, 2) }} {{ $sale->currency }}</td></tr>
        <tr><td>{{ __('Discount') }}</td><td style="text-align:right;">{{ number_format($sale->discount_amount, 2) }} {{ $sale->currency }}</td></tr>
        <tr><td>{{ __('Tax') }}</td><td style="text-align:right;">{{ number_format($sale->tax_amount, 2) }} {{ $sale->currency }}</td></tr>
        <tr><td><strong>{{ __('Total') }}</strong></td><td style="text-align:right;"><strong>{{ number_format($sale->total_amount, 2) }} {{ $sale->currency }}</strong></td></tr>
    </table>
    <div class="signature">
        <div>{{ __('Seller signature') }}</div>
        <div class="qr">
            <img src="data:image/png;base64,{{ $qrImage }}" alt="QR" width="110" height="110"><br>
            {{ __('Scan to verify sale record') }}
        </div>
    </div>
</body>
</html>
