<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Animal passport') }} — {{ $animal->displayIdentifier() }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 10px;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }
        /* BuchaPro: primary #a11d1e, burgundy #7a1516, charcoal #3c3c3b */
        .doc-header {
            border-bottom: 3px solid #a11d1e;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .doc-brand {
            font-size: 9px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
        }
        .doc-title {
            font-size: 18px;
            font-weight: bold;
            color: #a11d1e;
            margin: 0 0 6px;
        }
        .doc-meta {
            color: #64748b;
            font-size: 9px;
        }
        .doc-meta span { margin-right: 14px; }
        .hero {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 10px 12px;
            margin-bottom: 14px;
        }
        .hero-tag {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 2px;
        }
        .hero-code {
            font-size: 10px;
            color: #475569;
        }
        .panel {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            margin-bottom: 12px;
            overflow: hidden;
        }
        .panel-title {
            background: #a11d1e;
            border-bottom: 1px solid #7a1516;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.06em;
            margin: 0;
            padding: 6px 10px;
            text-transform: uppercase;
        }
        .panel-body { padding: 0; }
        table.kv { width: 100%; border-collapse: collapse; }
        table.kv td {
            border-bottom: 1px solid #f1f5f9;
            padding: 5px 10px;
            vertical-align: top;
        }
        table.kv tr:last-child td { border-bottom: none; }
        table.kv .label {
            color: #64748b;
            font-size: 9px;
            width: 42%;
        }
        table.kv .value {
            color: #0f172a;
            font-weight: bold;
            width: 58%;
        }
        .summary-list { margin: 0; padding: 8px 10px 10px 18px; }
        .summary-list li {
            color: #334155;
            margin-bottom: 4px;
            padding-left: 2px;
        }
        .qr-box {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 10px;
            text-align: center;
            background: #fff;
        }
        .qr-caption {
            color: #64748b;
            font-size: 8px;
            margin: 8px 0 0;
        }
        .qr-url {
            color: #a11d1e;
            font-size: 7px;
            margin-top: 4px;
            word-break: break-all;
        }
        .section { margin-top: 14px; page-break-inside: avoid; }
        .section-head {
            border-bottom: 2px solid #e2e8f0;
            color: #334155;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.04em;
            margin: 0 0 8px;
            padding-bottom: 4px;
            text-transform: uppercase;
        }
        .empty {
            color: #94a3b8;
            font-size: 9px;
            font-style: italic;
            margin: 0;
            padding: 4px 0;
        }
        .note {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 4px;
            color: #92400e;
            font-size: 8px;
            margin: 12px 0 0;
            padding: 6px 10px;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        table.data th {
            background: #a11d1e;
            border: 1px solid #7a1516;
            color: #ffffff;
            font-weight: bold;
            padding: 5px 6px;
            text-align: left;
        }
        table.data td {
            border: 1px solid #e2e8f0;
            padding: 5px 6px;
            vertical-align: top;
        }
        table.data tr:nth-child(even) td { background: #fafafa; }
        .muted { color: #64748b; }
        .footer {
            border-top: 1px solid #cbd5e1;
            color: #64748b;
            font-size: 8px;
            margin-top: 20px;
            padding-top: 10px;
        }
        .badge {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 3px;
            color: #047857;
            display: inline-block;
            font-size: 8px;
            font-weight: bold;
            padding: 2px 6px;
        }
    </style>
</head>
<body>
@php
    $verifyUrl = $animal->publicVerificationUrl() ?? route('animal.verify', ['token' => $animal->public_verification_token ?: $animal->animal_code]);
    $primaryId = $animal->tag_number ?: $animal->animal_code;
    $secondaryId = $animal->tag_number ? $animal->animal_code : null;
@endphp

    <div class="doc-header">
        <div class="doc-brand">{{ config('app.name', 'BuchaPro') }} · {{ __('Traceability') }}</div>
        <h1 class="doc-title">{{ __('Animal traceability passport') }}</h1>
        <div class="doc-meta">
            <span>{{ __('Generated') }}: {{ $generatedAt->format('d M Y, H:i') }}</span>
            @if ($certificate)
                <span>{{ __('Certificate') }}: {{ $certificate->certificate_number }}</span>
            @endif
        </div>
    </div>

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td colspan="2">
                <div class="hero">
                    <div class="hero-tag">{{ $primaryId }}</div>
                    @if ($secondaryId)
                        <div class="hero-code">{{ __('Animal code') }}: {{ $secondaryId }}</div>
                    @endif
                    @if ($animal->animal_name)
                        <div class="hero-code">{{ __('Name') }}: {{ $animal->animal_name }}</div>
                    @endif
                </div>
            </td>
        </tr>
        <tr>
            <td width="68%" valign="top" style="padding-right: 12px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="50%" valign="top" style="padding-right: 6px;">
                            <div class="panel">
                                <h2 class="panel-title">{{ __('Animal identity') }}</h2>
                                <div class="panel-body">
                                    <table class="kv">
                                        <tr><td class="label">{{ __('Gender') }}</td><td class="value">{{ ucfirst($animal->gender) }}</td></tr>
                                        <tr><td class="label">{{ __('Birth date') }}</td><td class="value">{{ $animal->birth_date?->format('d M Y') ?: '—' }}</td></tr>
                                        <tr><td class="label">{{ __('Acquisition') }}</td><td class="value">
                                            @if ($animal->acquisition_date)
                                                {{ $animal->acquisition_date->format('d M Y') }}
                                                @if ($animal->acquisition_type)
                                                    <br><span style="font-weight:normal;color:#64748b;">{{ \App\Models\Animal::acquisitionTypeLabel($animal->acquisition_type) }}</span>
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </td></tr>
                                        @if ($animal->livestock)
                                            <tr><td class="label">{{ __('Species / breed') }}</td><td class="value">
                                                {{ $animal->livestock->livestock_type ?: $animal->livestock->type ?: '—' }}
                                                @if ($animal->livestock->breed)
                                                    <br><span style="font-weight:normal;color:#64748b;">{{ $animal->livestock->breed }}</span>
                                                @endif
                                            </td></tr>
                                            <tr><td class="label">{{ __('Herd group') }}</td><td class="value">{{ $animal->livestock->livestock_name }}</td></tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </td>
                        <td width="50%" valign="top" style="padding-left: 6px;">
                            <div class="panel">
                                <h2 class="panel-title">{{ __('Farm origin') }}</h2>
                                <div class="panel-body">
                                    <table class="kv">
                                        <tr><td class="label">{{ __('Farm') }}</td><td class="value">{{ $summary['farm']?->name ?: '—' }}</td></tr>
                                        <tr><td class="label">{{ __('Registration') }}</td><td class="value">{{ $summary['farm']?->registration_number ?: '—' }}</td></tr>
                                        <tr><td class="label">{{ __('Owner') }}</td><td class="value">{{ $summary['business']?->ownerIndividualDisplayName() ?: '—' }}</td></tr>
                                        <tr><td class="label">{{ __('Location') }}</td><td class="value">{{ $summary['farm_location'] }}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>

                <div class="panel">
                    <h2 class="panel-title">{{ __('Health & traceability overview') }}</h2>
                    <div class="panel-body">
                        <table class="kv" style="margin-bottom:0;">
                            <tr>
                                <td class="label">{{ __('Health status') }}</td>
                                <td class="value">{{ ucfirst(str_replace('_', ' ', $animal->health_status)) }}</td>
                            </tr>
                            @if ($animal->current_condition)
                                <tr>
                                    <td class="label">{{ __('Current condition') }}</td>
                                    <td class="value">{{ \App\Models\Animal::currentConditionLabel($animal->current_condition) }}</td>
                                </tr>
                            @endif
                        </table>
                        <ul class="summary-list">
                            <li>{{ $summary['ownership_summary'] }}</li>
                            <li>{{ $summary['health_summary'] }}</li>
                            <li>{{ $summary['vaccination_summary'] }}</li>
                            <li>{{ $summary['last_treatment'] }}</li>
                            <li>{{ $summary['traceability_status'] }}</li>
                            @if (($summary['active_certificates'] ?? 0) > 0)
                                <li>{{ __('Active certificates on file') }}: {{ $summary['active_certificates'] }}</li>
                            @endif
                        </ul>
                    </div>
                </div>
            </td>
            <td width="32%" valign="top">
                <div class="qr-box">
                    <img src="{{ $qrImage }}" width="120" height="120" alt="QR">
                    <p class="qr-caption">{{ __('Scan to verify online') }}</p>
                    <p class="qr-url">{{ $verifyUrl }}</p>
                    @if ($certificate)
                        <p style="margin-top:8px;"><span class="badge">{{ ucfirst(str_replace('_', ' ', $certificate->certificate_status)) }}</span></p>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <p class="note">{{ __('Health tables list the most recent records (up to :max per category), newest first.', ['max' => $healthRecordLimit]) }}</p>

    <div class="section">
        <h2 class="section-head">{{ __('Vaccinations') }}</h2>
        @if ($vaccinations->isEmpty())
            <p class="empty">{{ __('No vaccination records on file.') }}</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Vaccine') }}</th>
                        <th>{{ __('Batch') }}</th>
                        <th>{{ __('Next due') }}</th>
                        <th>{{ __('Veterinarian') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vaccinations as $v)
                        <tr>
                            <td>{{ $v->vaccination_date?->format('d M Y') ?: '—' }}</td>
                            <td>
                                {{ $v->vaccine_name ?: '—' }}
                                @if ($v->vaccine_type)
                                    <br><span class="muted">{{ $v->vaccine_type }}</span>
                                @endif
                            </td>
                            <td>{{ $v->batch_number ?: '—' }}</td>
                            <td>{{ $v->next_due_date?->format('d M Y') ?: '—' }}</td>
                            <td>{{ $v->veterinarian_name ?: '—' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', (string) $v->status)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="section">
        <h2 class="section-head">{{ __('Treatments') }}</h2>
        @if ($treatments->isEmpty())
            <p class="empty">{{ __('No treatment records on file.') }}</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>{{ __('Period') }}</th>
                        <th>{{ __('Diagnosis') }}</th>
                        <th>{{ __('Medicine') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Veterinarian') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($treatments as $t)
                        <tr>
                            <td>
                                {{ $t->treatment_start_date?->format('d M Y') ?: '—' }}
                                @if ($t->treatment_end_date)
                                    <br><span class="muted">→ {{ $t->treatment_end_date->format('d M Y') }}</span>
                                @endif
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($t->disease_name ?: $t->diagnosis ?: '—'), 60) }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($t->medicine_name ?? '—'), 40) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', (string) $t->status)) }}</td>
                            <td>{{ $t->veterinarian_name ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="section">
        <h2 class="section-head">{{ __('Disease records') }}</h2>
        @if ($diseaseRecords->isEmpty())
            <p class="empty">{{ __('No disease records on file.') }}</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Disease') }}</th>
                        <th>{{ __('Severity') }}</th>
                        <th>{{ __('Recovery') }}</th>
                        <th>{{ __('Quarantine') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($diseaseRecords as $d)
                        <tr>
                            <td>{{ $d->diagnosis_date?->format('d M Y') ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($d->disease_name ?? '—'), 50) }}</td>
                            <td>{{ ucfirst((string) $d->severity_level) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', (string) $d->recovery_status)) }}</td>
                            <td>{{ $d->quarantine_required ? __('Yes') : __('No') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="section">
        <h2 class="section-head">{{ __('Veterinary visits') }}</h2>
        @if ($veterinaryVisits->isEmpty())
            <p class="empty">{{ __('No veterinary visit records on file.') }}</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Purpose') }}</th>
                        <th>{{ __('Veterinarian') }}</th>
                        <th>{{ __('Findings') }}</th>
                        <th>{{ __('Follow-up') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($veterinaryVisits as $visit)
                        <tr>
                            <td>{{ $visit->visit_date?->format('d M Y') ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($visit->purpose_of_visit ?? '—'), 55) }}</td>
                            <td>{{ $visit->veterinarian_name ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($visit->findings ?? '—'), 70) ?: '—' }}</td>
                            <td>
                                @if ($visit->follow_up_required)
                                    {{ __('Yes') }}@if($visit->follow_up_date) ({{ $visit->follow_up_date->format('d M Y') }})@endif
                                @else
                                    {{ __('No') }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="footer">
        {{ __('This document is generated from official traceability records. Scan the QR code or visit the verification link for the latest status.') }}
        · {{ config('app.name', 'BuchaPro') }}
    </div>
</body>
</html>
