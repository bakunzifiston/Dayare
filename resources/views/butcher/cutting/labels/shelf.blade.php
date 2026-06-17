<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $cutType->name }} — {{ $batch->batch_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 11px; margin: 0; padding: 8px; }
        .label { border: 2px solid #7f1d1d; border-radius: 4px; padding: 10px; }
        .brand { font-size: 13px; font-weight: bold; color: #7f1d1d; }
        .cut { font-size: 16px; font-weight: bold; margin: 6px 0 4px; }
        .row { margin: 3px 0; }
        .price { font-size: 14px; font-weight: bold; color: #7f1d1d; margin-top: 6px; }
    </style>
</head>
<body>
    <div class="label">
        <div class="brand">{{ $business->business_name ?: config('app.name') }}</div>
        <div class="cut">{{ $cutType->name }}</div>
        <div class="row"><strong>{{ __('Batch') }}:</strong> {{ $batch->batch_number }}</div>
        <div class="row"><strong>{{ __('Date') }}:</strong> {{ $session->session_date?->format('d M Y') }}</div>
        <div class="row"><strong>{{ __('Weight') }}:</strong> {{ number_format((float) $output->weight_kg, 2) }} kg</div>
        <div class="price">{{ __('Price') }}: RWF {{ number_format((float) $output->unit_cost_per_kg, 0) }}/kg</div>
        <div class="row" style="font-size:9px;color:#64748b;margin-top:4px;">{{ $session->session_number }}</div>
    </div>
</body>
</html>
