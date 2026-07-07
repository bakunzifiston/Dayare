<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Veterinary meat inspection certificate') }} — {{ $certificateNumber }}</title>
    <style>
        @page { margin: 10mm 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
            color: #1a1a1a;
            line-height: 1.3;
            margin: 0;
        }

        .cover {
            background-color: #00a651;
            color: #fff;
            text-align: center;
            padding: 10px 8px 12px;
            margin-bottom: 10px;
        }
        .flag-row { width: 100%; margin-bottom: 6px; }
        .flag-row td { padding: 0; height: 3px; }
        .flag-blue { background: #00a1de; }
        .flag-yellow { background: #fad201; }
        .flag-white { background: #ffffff; }
        .cover-republic {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .cover-facility {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 4px 0;
        }
        .cover-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .cover-badges {
            margin-top: 8px;
            font-size: 8px;
        }
        .badge {
            display: inline-block;
            border: 1px solid rgba(255,255,255,0.45);
            padding: 2px 6px;
            margin: 2px;
            border-radius: 3px;
        }
        .badge-highlight {
            background: rgba(255,255,255,0.15);
            font-weight: bold;
        }
        .cover-dates {
            margin-top: 6px;
            font-size: 8px;
            border-top: 1px solid rgba(255,255,255,0.25);
            padding-top: 5px;
        }

        .verify-row {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: separate;
            border-spacing: 4px 0;
        }
        .verify-row td {
            width: 33.33%;
            text-align: center;
            border: 1px solid #b8dcc6;
            background: #edf9f1;
            padding: 6px 4px;
            vertical-align: top;
        }
        .verify-row td.no {
            background: #fef2f2;
            border-color: #fecaca;
        }
        .verify-label {
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
        }
        .verify-value {
            font-size: 10px;
            font-weight: bold;
            color: #047857;
            margin-top: 2px;
        }
        .verify-row td.no .verify-value { color: #991b1b; }

        .section {
            margin-bottom: 8px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 9px;
            font-weight: bold;
            color: #047857;
            border-bottom: 2px solid #00a651;
            padding-bottom: 3px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .field-table, .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .field-table td, .data-table th, .data-table td {
            border: 1px solid #c8e6d4;
            padding: 4px 6px;
            vertical-align: top;
        }
        .field-label {
            width: 24%;
            font-size: 8px;
            font-weight: bold;
            color: #047857;
            background: #edf9f1;
        }
        .field-value {
            font-size: 9px;
            font-weight: 600;
        }
        .data-table th {
            font-size: 8px;
            font-weight: bold;
            color: #047857;
            background: #edf9f1;
            text-align: center;
        }
        .data-table td.num { text-align: center; }
        .data-table tr.total-row td {
            font-weight: bold;
            background: #e4f5ea;
        }

        .cert-note {
            font-size: 8px;
            color: #047857;
            margin: 4px 0 0;
        }

        .appendix-title {
            font-size: 9px;
            font-weight: bold;
            color: #047857;
            border-bottom: 1px solid #b8dcc6;
            padding-bottom: 3px;
            margin: 12px 0 6px;
            text-transform: uppercase;
        }
        .animal-row {
            border: 1px solid #c8e6d4;
            padding: 5px 6px;
            margin-bottom: 4px;
            background: #fafdfb;
        }
        .animal-tag {
            font-weight: bold;
            color: #047857;
        }

        .footer {
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px solid #c8e6d4;
            font-size: 7px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
@php
    $v = $certificateView;
@endphp

<div class="cover">
    <table class="flag-row" aria-hidden="true">
        <tr>
            <td class="flag-blue"></td>
            <td class="flag-yellow"></td>
            <td class="flag-white"></td>
        </tr>
    </table>
    <div class="cover-republic">{{ __('REPUBLIC OF RWANDA') }} · Republika y'u Rwanda</div>
    <div class="cover-facility">{{ $v['slaughterhouseDisplayName'] }}</div>
    <div class="cover-title">INYITO: ICYEMEZO CYA VETERINERI KU BUGENZUZI BW'INYAMA</div>
    <div class="cover-badges">
        <span class="badge">{{ $v['headerDistrictLine'] }}</span>
        <span class="badge">{{ $v['headerSectorLine'] }}</span>
        <span class="badge">{{ $v['headerCellLine'] }}</span>
        <span class="badge badge-highlight">{{ __('Certificate') }}: {{ $certificateNumber }}</span>
    </div>
    @if ($v['issuedAtFormatted'])
        <div class="cover-dates">
            <strong>Tariki</strong>: {{ $v['issuedAtFormatted'] }}
            @if ($certificate->expiry_date)
                · <strong>{{ __('Expires') }}</strong>: {{ $certificate->expiry_date->format('d/m/Y') }}
            @endif
        </div>
    @endif
</div>

<table class="verify-row">
    <tr>
        <td class="{{ $legallyInspected ? '' : 'no' }}">
            <div class="verify-label">{{ __('Legally inspected') }}</div>
            <div class="verify-value">{{ $legallyInspected ? __('Yes') : __('No') }}</div>
        </td>
        <td class="{{ $certificateValid ? '' : 'no' }}">
            <div class="verify-label">{{ __('Certificate valid') }}</div>
            <div class="verify-value">{{ $certificateValid ? __('Yes') : __('No') }}</div>
        </td>
        <td class="{{ $safeForSale ? '' : 'no' }}">
            <div class="verify-label">{{ __('Safe for sale') }}</div>
            <div class="verify-value">{{ $safeForSale ? __('Yes') : __('No') }}</div>
        </td>
    </tr>
</table>

@include('traceability.partials.certificate-sections-pdf', [
    'certificateView' => $certificateView,
    'certificateNumber' => $certificateNumber,
    'inspectorName' => $inspectorName,
    'slaughterDate' => $slaughterDate,
])

@if (! empty($animalsDetail))
    <div class="appendix-title">{{ __('Inspection traceability') }} ({{ count($animalsDetail) }})</div>
    @foreach ($animalsDetail as $animal)
        <div class="animal-row">
            <span class="animal-tag">{{ $animal['ear_tag'] }}</span>
            · {{ $animal['species'] }} · {{ $animal['sex'] }}
            · {{ __('PM') }}: {{ $animal['pm_outcome'] ?: __('Not recorded') }}
            @if ($animal['carcass_weight_kg'])
                · {{ number_format((float) $animal['carcass_weight_kg'], 2) }} kg
            @endif
        </div>
    @endforeach
@endif

<div class="footer">
    {{ config('app.name', 'BuchaPro') }} · {{ __('Meat traceability') }}
    · {{ __('Generated') }}: {{ $generatedAt->format('d/m/Y H:i') }}
</div>
</body>
</html>
