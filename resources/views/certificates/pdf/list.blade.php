<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Certificates Export</title>
    <style>
        @page { margin: 10mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5px;
            color: #111;
            line-height: 1.3;
            margin: 0;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: middle; }

        .title-band {
            background: #111;
            color: #fff;
            padding: 7px 10px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .title-band .count {
            float: right;
            font-size: 8.5px;
            letter-spacing: 0.05em;
        }

        .meta-table {
            background: #f6f7f8;
            border-bottom: 1px solid #111;
        }
        .meta-table td {
            padding: 4px 10px;
            font-size: 7.5px;
            border-right: 1px solid #d5d8dc;
        }
        .meta-table .meta-last { border-right: none; }
        .meta-key {
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #666;
        }
        .meta-val { font-weight: bold; font-size: 8.5px; }

        .rows-table { margin-top: 10px; }
        .rows-table th {
            background: #e9ebee;
            border-top: 1px solid #111;
            border-bottom: 1px solid #111;
            padding: 5px 8px;
            text-align: left;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #333;
        }
        .rows-table td {
            border-bottom: 1px solid #d5d8dc;
            padding: 5px 8px;
            font-size: 8.5px;
        }
        .rows-table .alt td { background: #fafbfc; }
        .rows-table .cert-no { font-weight: bold; }
        .rows-table .status {
            text-transform: uppercase;
            font-weight: bold;
            font-size: 7.5px;
            letter-spacing: 0.04em;
        }
        .rows-table .empty-row td {
            text-align: center;
            padding: 16px 8px;
            color: #777;
        }

        .footer-note {
            margin-top: 8px;
            font-size: 6.5px;
            color: #777;
        }
    </style>
</head>
<body>
@php
    $orAll = fn ($value, $fallback = 'All') => trim((string) $value) !== '' ? $value : $fallback;
@endphp

<div class="title-band">
    {{ __('Certificates Export') }}
    <span class="count">{{ trans_choice(':count certificate|:count certificates', $rows->count(), ['count' => $rows->count()]) }}</span>
</div>

<table class="meta-table">
    <tr>
        <td>
            <span class="meta-key">{{ __('Generated') }}</span><br>
            <span class="meta-val">{{ $generatedAt->format('d/m/Y H:i') }}</span>
        </td>
        <td>
            <span class="meta-key">{{ __('Search') }}</span><br>
            <span class="meta-val">{{ $orAll($filters['search']) }}</span>
        </td>
        <td>
            <span class="meta-key">{{ __('Status') }}</span><br>
            <span class="meta-val">{{ ucfirst($orAll($filters['status'])) }}</span>
        </td>
        <td>
            <span class="meta-key">{{ __('Facility') }}</span><br>
            <span class="meta-val">{{ $orAll($filters['facility_id']) }}</span>
        </td>
        <td class="meta-last">
            <span class="meta-key">{{ __('Issued between') }}</span><br>
            <span class="meta-val">{{ $orAll($filters['issued_from'], 'Any') }} — {{ $orAll($filters['issued_to'], 'Any') }}</span>
        </td>
    </tr>
</table>

<table class="rows-table">
    <thead>
        <tr>
            <th style="width: 4%;">{{ __('ID') }}</th>
            <th style="width: 19%;">{{ __('Certificate Number') }}</th>
            <th style="width: 14%;">{{ __('Batch') }}</th>
            <th style="width: 20%;">{{ __('Facility') }}</th>
            <th style="width: 15%;">{{ __('Inspector') }}</th>
            <th style="width: 10%;">{{ __('Issued') }}</th>
            <th style="width: 10%;">{{ __('Expires') }}</th>
            <th style="width: 8%;">{{ __('Status') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $cert)
            <tr @class(['alt' => $loop->even])>
                <td>{{ $cert->id }}</td>
                <td class="cert-no">{{ $cert->certificate_number ?: '—' }}</td>
                <td>{{ $cert->batch?->batch_code ?: '—' }}</td>
                <td>{{ $cert->facility?->facility_name ?: '—' }}</td>
                <td>{{ $cert->inspector?->full_name ?: '—' }}</td>
                <td>{{ $cert->issued_at?->format('d/m/Y') ?: '—' }}</td>
                <td>{{ $cert->expiry_date?->format('d/m/Y') ?: '—' }}</td>
                <td class="status">{{ ucfirst((string) $cert->status) }}</td>
            </tr>
        @empty
            <tr class="empty-row">
                <td colspan="8">{{ __('No certificates found.') }}</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="footer-note">{{ __('Scan the QR code on an individual certificate to verify its traceability record.') }}</div>
</body>
</html>
