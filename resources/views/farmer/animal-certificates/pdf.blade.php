<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->certificate_title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 12px; }
        .header { border-bottom: 2px solid #7f1d1d; padding-bottom: 12px; margin-bottom: 16px; }
        .title { font-size: 22px; font-weight: bold; color: #7f1d1d; }
        .section { margin-top: 16px; }
        .section h3 { font-size: 13px; margin: 0 0 8px; text-transform: uppercase; letter-spacing: 0.04em; }
        .grid td { padding: 4px 8px 4px 0; vertical-align: top; }
        .footer { margin-top: 24px; border-top: 1px solid #cbd5e1; padding-top: 12px; font-size: 10px; color: #64748b; }
        .watermark { position: fixed; top: 40%; left: 20%; font-size: 48px; color: rgba(15,23,42,0.05); transform: rotate(-25deg); }
    </style>
</head>
<body>
    @if ($certificate->template?->watermark_text)
        <div class="watermark">{{ $certificate->template->watermark_text }}</div>
    @endif
    <div class="header">
        <div class="title">{{ $certificate->certificate_title }}</div>
        <div>{{ __('Certificate number') }}: {{ $certificate->certificate_number }}</div>
        <div>{{ __('Verify') }}: {{ $certificate->verificationUrl() }}</div>
    </div>
    <table width="100%"><tr><td>
        <div class="section"><h3>{{ __('Animal information') }}</h3><table class="grid">@foreach ([__('Animal code') => $summary['animal']->animal_code, __('Tag number') => $summary['animal']->tag_number ?: '—', __('Gender') => ucfirst($summary['animal']->gender), __('Health status') => ucfirst(str_replace('_', ' ', $summary['animal']->health_status)), __('Lifecycle status') => ucfirst(str_replace('_', ' ', $summary['animal']->lifecycle_status))] as $label => $value)<tr><td><strong>{{ $label }}</strong></td><td>{{ $value }}</td></tr>@endforeach</table></div>
        <div class="section"><h3>{{ __('Farm information') }}</h3><table class="grid"><tr><td><strong>{{ __('Farm') }}</strong></td><td>{{ $summary['farm']?->name ?: '—' }}</td></tr><tr><td><strong>{{ __('Owner') }}</strong></td><td>{{ $summary['business']?->name ?: '—' }}</td></tr><tr><td><strong>{{ __('Location') }}</strong></td><td>{{ $summary['farm_location'] }}</td></tr></table></div>
        <div class="section"><h3>{{ __('Traceability summary') }}</h3><p>{{ $summary['ownership_summary'] }}</p><p>{{ $summary['health_summary'] }}</p><p>{{ $summary['vaccination_summary'] }}</p><p>{{ $summary['last_treatment'] }}</p><p>{{ $summary['feeding_summary'] }}</p></div>
    </td><td width="160" align="right"><img src="{{ $qrImage }}" width="140" height="140" alt="QR"></td></tr></table>
    <div class="section"><h3>{{ __('Certification') }}</h3><p>{{ __('Issued by') }}: {{ $certificate->issued_by ?: '—' }}</p><p>{{ __('Veterinarian') }}: {{ $certificate->veterinarian_name ?: '—' }}</p><p>{{ __('Issue date') }}: {{ $certificate->issue_date?->toDateString() }}</p><p>{{ __('Expiry date') }}: {{ $certificate->expiry_date?->toDateString() ?: '—' }}</p><p>{{ __('Digital signature') }}: {{ \Illuminate\Support\Str::limit($certificate->digital_signature, 32, '…') }}</p></div>
    <div class="footer">{{ $certificate->template?->footer_note ?: __('This certificate is digitally verifiable through the DayareMeat traceability portal.') }}</div>
</body>
</html>
