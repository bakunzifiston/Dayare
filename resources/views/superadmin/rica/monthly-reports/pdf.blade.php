<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Private Meat Inspection Report') }}</title>
    <style>
        @page { margin: 10mm 12mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1a1a1a;
            line-height: 1.4;
        }
        .page-footer {
            position: fixed;
            bottom: -8mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7.5px;
            color: #047857;
        }
        .cover {
            background-color: #00a651;
            color: #ffffff;
            padding: 10px 12px 12px;
            margin: 0 0 12px;
            text-align: center;
        }
        .cover-flag td {
            height: 3px;
            padding: 0;
            border: none;
        }
        .cover-flag .flag-blue { background-color: #00a1de; }
        .cover-flag .flag-yellow { background-color: #fad201; }
        .cover-flag .flag-green { background-color: #ffffff; }
        .cover-republic {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-top: 8px;
        }
        .cover-republic-rw {
            font-size: 8px;
            margin-top: 2px;
            opacity: 0.9;
        }
        .cover-title {
            margin-top: 8px;
            font-size: 11px;
            font-weight: bold;
            line-height: 1.3;
        }
        .cover-title-rw {
            font-size: 8.5px;
            margin-top: 3px;
            opacity: 0.92;
        }
        .cover-note {
            margin-top: 6px;
            font-size: 7.5px;
            font-style: italic;
            opacity: 0.9;
        }
        .cover-badges {
            margin-top: 8px;
            font-size: 7.5px;
        }
        .cover-badge {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 10px;
            padding: 2px 8px;
            margin: 2px 3px;
        }
        .cover-badge--highlight {
            background-color: rgba(255, 255, 255, 0.28);
            font-weight: bold;
        }
        .cover-meta {
            width: 100%;
            margin-top: 8px;
            border-collapse: collapse;
        }
        .cover-meta td {
            font-size: 8px;
            padding: 3px 4px;
            vertical-align: top;
            color: #ffffff;
        }
        .section {
            margin-top: 11px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 9px;
            font-weight: bold;
            color: #047857;
            border-bottom: 2px solid #00a651;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }
        .section-num {
            color: #00a651;
            font-weight: bold;
        }
        .section-title-rw {
            font-size: 7.5px;
            font-weight: normal;
            color: #333;
            text-transform: none;
        }
        .section-hint {
            font-size: 7px;
            color: #555;
            margin: -2px 0 6px;
            font-style: italic;
        }
        .field-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
        }
        .field-table td {
            padding: 4px 6px;
            vertical-align: top;
            border: 1px solid #c8e6d4;
        }
        .field-label {
            width: 26%;
            font-size: 7.5px;
            color: #047857;
            background-color: #edf9f1;
            font-weight: bold;
        }
        .field-value {
            font-weight: 600;
            font-size: 8.5px;
            color: #111;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #b8dcc6;
            padding: 4px 5px;
            font-size: 8px;
            vertical-align: top;
        }
        .data-table th {
            background-color: #edf9f1;
            color: #047857;
            font-weight: bold;
            text-align: left;
        }
        .data-table tbody tr:nth-child(even) td {
            background-color: #fafdfb;
        }
        .data-table tfoot td {
            font-weight: bold;
            background-color: #e4f5ea;
            color: #047857;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .empty-note {
            font-style: italic;
            color: #666;
            padding: 8px 4px;
            text-align: center;
        }
        .page-break { page-break-before: always; }
        .signature-block {
            margin-top: 6px;
            border: 1px solid #b8dcc6;
            background-color: #fafdfb;
            padding: 6px 8px;
        }
        .challenges-box {
            border: 1px solid #b8dcc6;
            background-color: #fafdfb;
            min-height: 70px;
            margin-top: 5px;
            padding: 8px;
            font-size: 8.5px;
        }
        .sub-heading {
            font-size: 8px;
            font-weight: bold;
            color: #047857;
            margin: 8px 0 4px;
        }
        .cert-note {
            font-size: 8px;
            color: #047857;
            margin-bottom: 5px;
        }
        .submitted-note {
            font-size: 7.5px;
            margin-top: 8px;
            color: #047857;
            font-weight: bold;
        }
    </style>
</head>
<body>
@php
    $meta = $report['meta'];
    $inspector = $report['inspector'];
    $sh = $report['slaughterhouse'];
    $fmtDate = function ($date) {
        if ($date === null || $date === '') {
            return '—';
        }
        if ($date instanceof \Carbon\CarbonInterface) {
            return $date->format('d/m/Y');
        }
        return \Carbon\Carbon::parse($date)->format('d/m/Y');
    };
    $fmtSigned = function ($value) use ($fmtDate) {
        if ($value === null || $value === '') {
            return '—';
        }
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('d/m/Y H:i');
        }
        return \Carbon\Carbon::parse($value)->format('d/m/Y H:i');
    };
    $closure = $report['closure'];
    $effectiveDate = \Carbon\Carbon::parse($meta['effective_date'])->format('jS F Y');
@endphp

    <div class="cover">
        <table class="cover-flag" style="width:100%; border-collapse:collapse; margin-bottom:0;">
            <tr>
                <td class="flag-blue" style="width:33%;"></td>
                <td class="flag-yellow" style="width:34%;"></td>
                <td class="flag-green" style="width:33%;"></td>
            </tr>
        </table>
        <div class="cover-republic">{{ __('REPUBLIC OF RWANDA') }}</div>
        <div class="cover-republic-rw">{{ __('REPUBULIKA Y\'U RWANDA') }}</div>
        <div class="cover-title">{{ __('TITLE: PRIVATE MEAT INSPECTION REPORT FORM') }}</div>
        <div class="cover-title-rw">{{ __('IFISHI YA RAPORO Y\'UMUGENZUZI W\'INYAMA WIGENGA') }}</div>
        <div class="cover-note">{{ __('The activities done are reported monthly') }} / {{ __('Ibikorwa byakozwe bitangirwa raporo buri kwezi') }}</div>
        <div class="cover-badges">
            <span class="cover-badge">{{ $meta['form_id'] }} · {{ __('Rev.') }} {{ $meta['revision'] }}</span>
            <span class="cover-badge">{{ __('Effective') }}: {{ $effectiveDate }}</span>
            <span class="cover-badge cover-badge--highlight">{{ __('Report') }}: {{ $meta['report_number'] }}</span>
            <span class="cover-badge cover-badge--highlight">{{ __('Period') }}: {{ $meta['period_label'] }}</span>
        </div>
        <table class="cover-meta">
            <tr>
                <td style="width:50%; text-align:left;">
                    <strong>{{ __('Reporting Date') }}</strong> / {{ __('Itariki') }}:
                    {{ $fmtDate($meta['reporting_date']) }}
                </td>
                <td style="width:50%; text-align:right;">
                    {{ $meta['period_start']->format('d/m/Y') }} – {{ $meta['period_end']->format('d/m/Y') }}
                </td>
            </tr>
        </table>
    </div>

    {{-- Section 1 --}}
    <div class="section">
        <div class="section-title">
            <span class="section-num">1.</span> {{ __('PRIVATE MEAT INSPECTOR DETAILS') }}
            <span class="section-title-rw">/ {{ __('AMAKURU Y\'UMUGENZUZI W\'INYAMA WIGENGA') }}</span>
        </div>
        @if ($inspector)
            <table class="field-table">
                <tr>
                    <td class="field-label">{{ __('Names') }} / {{ __('Amazina') }}</td>
                    <td class="field-value" colspan="3">{{ $inspector['name'] }}</td>
                </tr>
                <tr>
                    <td class="field-label">{{ __('Email') }} / {{ __('Imeli') }}</td>
                    <td class="field-value">{{ $inspector['email'] ?: '—' }}</td>
                    <td class="field-label">{{ __('Phone') }} / {{ __('Telefoni') }}</td>
                    <td class="field-value">{{ $inspector['phone'] ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="field-label">{{ __('Authorization No.') }}</td>
                    <td class="field-value">{{ $inspector['authorization_number'] ?: '—' }}</td>
                    <td class="field-label">{{ __('Issue date') }} / {{ __('Igihe yatangiwe') }}</td>
                    <td class="field-value">{{ $fmtDate($inspector['authorization_issue_date']) }}</td>
                </tr>
            </table>
        @else
            <p class="empty-note">{{ __('No inspector activity recorded for this period.') }}</p>
        @endif
    </div>

    {{-- Section 2 --}}
    <div class="section">
        <div class="section-title">
            <span class="section-num">2.</span> {{ __('SLAUGHTERHOUSE DETAILS') }}
            <span class="section-title-rw">/ {{ __('AMAKURU Y\'IBAGIRO') }}</span>
        </div>
        <table class="field-table">
            <tr>
                <td class="field-label">{{ __('Names') }} / {{ __('Amazina') }}</td>
                <td class="field-value" colspan="3">{{ $sh['name'] }}</td>
            </tr>
            <tr>
                <td class="field-label">{{ __('Operator') }}</td>
                <td class="field-value" colspan="3">{{ $sh['operator_name'] ?: '—' }}</td>
            </tr>
            <tr>
                <td class="field-label">{{ __('Registration No.') }}</td>
                <td class="field-value">{{ $sh['registration_number'] ?: '—' }}</td>
                <td class="field-label">{{ __('License No.') }}</td>
                <td class="field-value">{{ $sh['license_number'] ?: '—' }}</td>
            </tr>
            <tr>
                <td class="field-label">{{ __('License issue date') }}</td>
                <td class="field-value">{{ $fmtDate($sh['license_issue_date']) }}</td>
                <td class="field-label">{{ __('Phone') }} / {{ __('Telefoni') }}</td>
                <td class="field-value">{{ $sh['phone'] ?: '—' }}</td>
            </tr>
            <tr>
                <td class="field-label">{{ __('Email') }} / {{ __('Imeli') }}</td>
                <td class="field-value" colspan="3">{{ $sh['email'] ?: '—' }}</td>
            </tr>
            <tr>
                <td class="field-label">{{ __('District') }} / {{ __('Akarere') }}</td>
                <td class="field-value">{{ $sh['district'] ?: '—' }}</td>
                <td class="field-label">{{ __('Sector') }} / {{ __('Umurenge') }}</td>
                <td class="field-value">{{ $sh['sector'] ?: '—' }}</td>
            </tr>
            <tr>
                <td class="field-label">{{ __('Cell') }} / {{ __('Akagari') }}</td>
                <td class="field-value">{{ $sh['cell'] ?: '—' }}</td>
                <td class="field-label">{{ __('Village') }} / {{ __('Umudugudu') }}</td>
                <td class="field-value">{{ $sh['village'] ?: '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- Section 3 --}}
    <div class="section">
        <div class="section-title">
            <span class="section-num">3.</span> {{ __('RECEIVED ANIMALS DETAILS') }}
            <span class="section-title-rw">/ {{ __('AMAKURU Y\'AMATUNGO YAKIRIWE') }}</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('Origin') }} / {{ __('Inkomoko') }}</th>
                    <th>{{ __('Species') }} / {{ __('Ubwoko') }}</th>
                    <th class="text-center">{{ __('Male') }} / {{ __('Gabo') }}</th>
                    <th class="text-center">{{ __('Female') }} / {{ __('Gore') }}</th>
                    <th>{{ __('Comment') }} / {{ __('Icyongerwaho') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['received_animals']['rows'] as $row)
                    <tr>
                        <td>{{ $row['origin'] }}</td>
                        <td>{{ $row['species'] }}</td>
                        <td class="text-center">{{ $row['male'] }}</td>
                        <td class="text-center">{{ $row['female'] }}</td>
                        <td>{{ $row['comment'] ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-note text-center">{{ __('No animals slaughtered in this period.') }}</td></tr>
                @endforelse
            </tbody>
            @if (count($report['received_animals']['totals_by_species']) > 0)
                <tfoot>
                    <tr>
                        <td colspan="5"><strong>{{ __('Total received animal per sex') }} / {{ __('Igiteranyo kuri buri gitsina') }}</strong></td>
                    </tr>
                    @foreach ($report['received_animals']['totals_by_species'] as $total)
                        <tr>
                            <td></td>
                            <td>{{ $total['species'] }}</td>
                            <td class="text-center">{{ $total['male'] }}</td>
                            <td class="text-center">{{ $total['female'] }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                </tfoot>
            @endif
        </table>
    </div>

    <div class="page-break"></div>

    {{-- Section 4 --}}
    <div class="section">
        <div class="section-title">
            <span class="section-num">4.</span> {{ __('ANTE-MORTEM INSPECTION DETAILS') }}
            <span class="section-title-rw">/ {{ __('AMAKURU Y\'IGENZURA RYA MBERE YO KUBAGA') }}</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('Species') }} / {{ __('Ubwoko') }}</th>
                    <th class="text-center">{{ __('No. of healthy animals') }} / {{ __('Umubare w\'amatungo meza') }}</th>
                    <th class="text-center">{{ __('Number') }} / {{ __('Umubare') }}</th>
                    <th>{{ __('Conditions') }} / {{ __('Imiterere') }}</th>
                    <th>{{ __('Action taken') }} / {{ __('Igikorwa cyakozwe') }}</th>
                    <th>{{ __('Final action') }} / {{ __('Icyemezo') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['ante_mortem']['rows'] as $row)
                    @if ($row['unhealthy_count'] === 0)
                        <tr>
                            <td>{{ $row['species'] }}</td>
                            <td class="text-center">{{ $row['healthy'] }}</td>
                            <td class="text-center">0</td>
                            <td>{{ __('None recorded') }}</td>
                            <td>{{ __('None recorded') }}</td>
                            <td>{{ __('None recorded') }}</td>
                        </tr>
                    @else
                        @foreach ($row['unhealthy'] as $unhealthy)
                            <tr>
                                @if ($loop->first)
                                    <td rowspan="{{ $row['unhealthy_count'] }}">{{ $row['species'] }}</td>
                                    <td rowspan="{{ $row['unhealthy_count'] }}" class="text-center">{{ $row['healthy'] }}</td>
                                @endif
                                <td class="text-center">{{ $unhealthy['number'] }}</td>
                                <td>{{ $unhealthy['conditions'] }}</td>
                                <td>{{ $unhealthy['action_taken'] }}</td>
                                <td>{{ $unhealthy['final_action'] }}</td>
                            </tr>
                        @endforeach
                    @endif
                @empty
                    <tr>
                        <td colspan="6" class="empty-note text-center">
                            {{ __('No ante-mortem inspections recorded for this period.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Section 5 --}}
    <div class="section">
        <div class="section-title">
            <span class="section-num">5.</span> {{ __('POST-MORTEM INSPECTION DETAILS') }}
            <span class="section-title-rw">/ {{ __('AMAKURU Y\'IGENZURA RYA NYUMA YO KUBAGA') }}</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('Species') }} / {{ __('Ubwoko') }}</th>
                    <th class="text-center">{{ __('No. of approved carcasses') }} / {{ __('Umubare w\'inyama yemewe') }}</th>
                    <th class="text-center">{{ __('Number') }} / {{ __('Umubare') }}</th>
                    <th>{{ __('Seized part / organ') }} / {{ __('Igice cyafashwe') }}</th>
                    <th>{{ __('Reason') }} / {{ __('Impamvu') }}</th>
                    <th class="text-right">{{ __('Qty (kg)') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['post_mortem']['rows'] as $row)
                    @if ($row['condemned_count'] === 0)
                        <tr>
                            <td>{{ $row['species'] }}</td>
                            <td class="text-center">{{ $row['approved'] }}</td>
                            <td class="text-center">0</td>
                            <td>{{ __('None recorded') }}</td>
                            <td>{{ __('None recorded') }}</td>
                            <td class="text-right">0.00</td>
                        </tr>
                    @else
                        @foreach ($row['condemned'] as $condemned)
                            <tr>
                                @if ($loop->first)
                                    <td rowspan="{{ $row['condemned_count'] }}">{{ $row['species'] }}</td>
                                    <td rowspan="{{ $row['condemned_count'] }}" class="text-center">{{ $row['approved'] }}</td>
                                @endif
                                <td class="text-center">{{ $condemned['number'] }}</td>
                                <td>{{ $condemned['seized_part'] }}</td>
                                <td>{{ $condemned['reason'] }}</td>
                                <td class="text-right">{{ number_format($condemned['qty_kg'], 2) }}</td>
                            </tr>
                        @endforeach
                    @endif
                @empty
                    <tr>
                        <td colspan="6" class="empty-note text-center">
                            {{ __('No post-mortem inspections recorded for this period.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if (count($report['post_mortem']['totals_by_species']) > 0)
                <tfoot>
                    <tr>
                        <td colspan="6"><strong>{{ __('Total of rejected meat per species') }}</strong></td>
                    </tr>
                    @foreach ($report['post_mortem']['totals_by_species'] as $total)
                        <tr>
                            <td>{{ $total['species'] }}</td>
                            <td colspan="4"></td>
                            <td class="text-right">{{ number_format($total['qty_kg'], 2) }}</td>
                        </tr>
                    @endforeach
                </tfoot>
            @endif
        </table>
    </div>

    <div class="page-break"></div>

    {{-- Section 6 --}}
    <div class="section">
        <div class="section-title">
            <span class="section-num">6.</span> {{ __('MEAT SUPPLY DETAILS') }}
            <span class="section-title-rw">/ {{ __('AMAKURU AJYANYE NO KUGEMURA INYAMA') }}</span>
        </div>
        @if ($report['meat_supply']['certificate_serial_range']['start'])
            <p class="cert-note">
                <strong>{{ __('Certificates') }}:</strong>
                {{ $report['meat_supply']['certificate_serial_range']['start'] }}
                @if ($report['meat_supply']['certificate_serial_range']['end'] && $report['meat_supply']['certificate_serial_range']['end'] !== $report['meat_supply']['certificate_serial_range']['start'])
                    – {{ $report['meat_supply']['certificate_serial_range']['end'] }}
                @endif
            </p>
        @endif
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('Species') }}</th>
                    <th class="text-right">{{ __('Qty (kg)') }}</th>
                    <th>{{ __('Certificate No.') }}</th>
                    <th>{{ __('District') }}</th>
                    <th>{{ __('Sector') }}</th>
                    <th>{{ __('Other') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['meat_supply']['rows'] as $row)
                    <tr>
                        <td>{{ $row['species'] }}</td>
                        <td class="text-right">{{ number_format($row['qty_kg'], 2) }}</td>
                        <td>{{ $row['certificate_number'] }}</td>
                        <td>{{ $row['destination_district'] ?: '—' }}</td>
                        <td>{{ $row['destination_sector'] ?: '—' }}</td>
                        <td>{{ $row['destination_other'] ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-note text-center">{{ __('No certificates issued in this period.') }}</td></tr>
                @endforelse
            </tbody>
            @if (count($report['meat_supply']['totals_by_species']) > 0)
                <tfoot>
                    <tr>
                        <td colspan="6"><strong>{{ __('Total delivered meat per species') }}</strong></td>
                    </tr>
                    @foreach ($report['meat_supply']['totals_by_species'] as $total)
                        <tr>
                            <td>{{ $total['species'] }}</td>
                            <td class="text-right">{{ number_format($total['qty_kg'], 2) }}</td>
                            <td colspan="4"></td>
                        </tr>
                    @endforeach
                </tfoot>
            @endif
        </table>
    </div>

    <div class="page-break"></div>

    {{-- Section 7 --}}
    <div class="section">
        <div class="section-title">
            <span class="section-num">7.</span> {{ __('CHALLENGES') }}
            <span class="section-title-rw">/ {{ __('IMBOGAMIZI') }}</span>
        </div>
        <div class="challenges-box" style="white-space:pre-wrap;">{{ $closure['challenges'] ?? '' }}</div>
    </div>

    {{-- Section 8 --}}
    <div class="section">
        <div class="section-title">
            <span class="section-num">8.</span> {{ __('CONCLUSION & RECOMMENDATIONS') }}
            <span class="section-title-rw">/ {{ __('UMWANZURO N\'INAMA') }}</span>
        </div>
        @if (! empty($closure['recommendations']))
            <div class="challenges-box" style="min-height:40px; white-space:pre-wrap;">{{ $closure['recommendations'] }}</div>
        @endif
        <p class="sub-heading">
            {{ __('Private Meat Inspector(s)') }} / {{ __('Umu(aba)genzuzi w\'(b\')inyama w(b)igenga') }}
        </p>
        @foreach ($closure['inspector_signatures'] ?? [] as $index => $signature)
            <div class="signature-block">
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="width:55%; font-size:8px;">{{ $index + 1 }}. {{ __('Names') }} / {{ __('Amazina') }}:</td>
                        <td style="width:22%; font-size:8px;">{{ __('Signature') }} / {{ __('Umukono') }}:</td>
                        <td style="width:23%; font-size:8px;">{{ __('Date') }} / {{ __('Itariki') }}:</td>
                    </tr>
                    <tr>
                        <td style="font-size:9px; font-weight:600; padding-top:4px;">{{ $signature['name'] ?? '' }}</td>
                        <td style="font-size:8px; padding-top:4px;">{{ filled($signature['signed_at'] ?? null) ? __('Signed') : '' }}</td>
                        <td style="font-size:8px; padding-top:4px;">{{ filled($signature['signed_at'] ?? null) ? $fmtSigned($signature['signed_at']) : '' }}</td>
                    </tr>
                </table>
            </div>
        @endforeach
        <p class="sub-heading">
            {{ __('Slaughterhouse operator') }} / {{ __('Ucunga ibagiro') }}
        </p>
        <div class="signature-block">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="width:55%; font-size:8px;">{{ __('Names') }} / {{ __('Amazina') }}:</td>
                    <td style="width:22%; font-size:8px;">{{ __('Signature') }} / {{ __('Umukono') }}:</td>
                    <td style="width:23%; font-size:8px;">{{ __('Date') }} / {{ __('Itariki') }}:</td>
                </tr>
                <tr>
                    <td style="font-size:9px; font-weight:600; padding-top:4px;">{{ $closure['operator_name'] ?? '' }}</td>
                    <td style="font-size:8px; padding-top:4px;">{{ filled($closure['operator_signed_at'] ?? null) ? __('Signed') : '' }}</td>
                    <td style="font-size:8px; padding-top:4px;">{{ filled($closure['operator_signed_at'] ?? null) ? $fmtSigned($closure['operator_signed_at']) : '' }}</td>
                </tr>
            </table>
        </div>
        <p class="sub-heading" style="margin-top:8px;">
            {{ __('Slaughterhouse stamp') }} / {{ __('Kashi y\'ibagiro') }}:
            <span style="font-weight:normal;">{{ ($closure['stamp_acknowledged'] ?? false) ? __('Confirmed') : '—' }}</span>
        </p>
        @if (($closure['status'] ?? '') === 'submitted')
            <p class="submitted-note">
                {{ __('Submitted to RICA') }}:
                {{ $fmtSigned($closure['submitted_at'] ?? null) }}
                @if (! empty($closure['submitted_by_name']))
                    · {{ $closure['submitted_by_name'] }}
                @endif
            </p>
        @endif
    </div>

    <div class="page-footer">
        {{ $meta['form_id'] }} · {{ __('Revision') }} {{ $meta['revision'] }} · {{ __('Generated from Buchapro') }}
    </div>
</body>
</html>
