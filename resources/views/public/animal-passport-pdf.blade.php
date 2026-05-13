<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Animal passport') }} — {{ $animal->displayIdentifier() }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 11px; line-height: 1.35; }
        .header { border-bottom: 2px solid #7f1d1d; padding-bottom: 10px; margin-bottom: 14px; }
        .title { font-size: 20px; font-weight: bold; color: #7f1d1d; }
        .section { margin-top: 12px; }
        .section h3 { font-size: 11px; margin: 0 0 6px; text-transform: uppercase; letter-spacing: 0.04em; color: #334155; }
        .grid td { padding: 3px 10px 3px 0; vertical-align: top; }
        .footer { margin-top: 18px; border-top: 1px solid #cbd5e1; padding-top: 10px; font-size: 9px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ __('Animal traceability passport') }}</div>
        <div>{{ __('Generated') }}: {{ $generatedAt->toDateTimeString() }}</div>
        @if ($certificate)
            <div>{{ __('Linked certificate') }}: {{ $certificate->certificate_number }} — {{ ucfirst(str_replace('_', ' ', $certificate->certificate_status)) }}</div>
        @endif
    </div>
    <table width="100%">
        <tr>
            <td>
                <div class="section">
                    <h3>{{ __('Animal identity') }}</h3>
                    <table class="grid">
                        <tr><td><strong>{{ __('Animal code') }}</strong></td><td>{{ $animal->animal_code }}</td></tr>
                        <tr><td><strong>{{ __('Tag number') }}</strong></td><td>{{ $animal->tag_number ?: '—' }}</td></tr>
                        <tr><td><strong>{{ __('Name') }}</strong></td><td>{{ $animal->animal_name ?: '—' }}</td></tr>
                        <tr><td><strong>{{ __('Gender') }}</strong></td><td>{{ ucfirst($animal->gender) }}</td></tr>
                        <tr><td><strong>{{ __('Birth date') }}</strong></td><td>{{ $animal->birth_date?->toDateString() ?: '—' }}</td></tr>
                        <tr><td><strong>{{ __('Acquisition') }}</strong></td><td>{{ $animal->acquisition_date?->toDateString() ?: '—' }} @if($animal->acquisition_type) ({{ str_replace('_', ' ', $animal->acquisition_type) }}) @endif</td></tr>
                        <tr><td><strong>{{ __('Health status') }}</strong></td><td>{{ ucfirst(str_replace('_', ' ', $animal->health_status)) }}</td></tr>
                        <tr><td><strong>{{ __('Production status') }}</strong></td><td>{{ ucfirst(str_replace('_', ' ', $animal->production_status)) }}</td></tr>
                        <tr><td><strong>{{ __('Lifecycle status') }}</strong></td><td>{{ ucfirst(str_replace('_', ' ', $animal->lifecycle_status)) }}</td></tr>
                        @if ($animal->livestock)
                            <tr><td><strong>{{ __('Livestock group') }}</strong></td><td>{{ $animal->livestock->livestock_name }} @if($animal->livestock->breed) — {{ $animal->livestock->breed }} @endif</td></tr>
                            <tr><td><strong>{{ __('Species / type') }}</strong></td><td>{{ $animal->livestock->livestock_type ?: $animal->livestock->type ?: '—' }}</td></tr>
                        @endif
                    </table>
                </div>
                <div class="section">
                    <h3>{{ __('Farm origin') }}</h3>
                    <table class="grid">
                        <tr><td><strong>{{ __('Farm') }}</strong></td><td>{{ $summary['farm']?->name ?: '—' }}</td></tr>
                        <tr><td><strong>{{ __('Registration number') }}</strong></td><td>{{ $summary['farm']?->registration_number ?: '—' }}</td></tr>
                        <tr><td><strong>{{ __('Owner') }}</strong></td><td>{{ $summary['business']?->business_name ?: '—' }}</td></tr>
                        <tr><td><strong>{{ __('Current owner (on file)') }}</strong></td><td>{{ $summary['current_owner'] }}</td></tr>
                        <tr><td><strong>{{ __('Location') }}</strong></td><td>{{ $summary['farm_location'] }}</td></tr>
                    </table>
                </div>
                <div class="section">
                    <h3>{{ __('Traceability & health summary') }}</h3>
                    <p>{{ $summary['ownership_summary'] }}</p>
                    <p>{{ $summary['health_summary'] }}</p>
                    <p>{{ $summary['vaccination_summary'] }}</p>
                    <p>{{ $summary['last_treatment'] }}</p>
                    <p>{{ $summary['feeding_summary'] }}</p>
                    <p>{{ $summary['traceability_status'] }}</p>
                    @if (($summary['active_certificates'] ?? 0) > 0)
                        <p>{{ __('Active certificates on file') }}: {{ $summary['active_certificates'] }}</p>
                    @endif
                </div>
            </td>
            <td width="150" align="right" valign="top">
                <img src="{{ $qrImage }}" width="130" height="130" alt="QR">
                <p style="font-size:9px;color:#64748b;margin-top:6px;">{{ __('Scan to verify online') }}</p>
            </td>
        </tr>
    </table>
    <div class="footer">{{ __('This document is generated from BuchaPro records. Verify the latest status using the QR code or your official verification link.') }}</div>
</body>
</html>
