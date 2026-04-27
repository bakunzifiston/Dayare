<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Traceability') }} — {{ $certificateNumber }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; padding: 1rem; max-width: 32rem; margin: 0 auto; background: #f8fafc; }
        .card { background: #fff; border-radius: 1rem; padding: 1.25rem; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #e2e8f0; }
        h1 { font-size: 1.35rem; font-weight: 700; color: #0f172a; margin: 0 0 0.5rem 0; }
        .subtitle { font-size: 0.875rem; color: #64748b; margin-bottom: 1.25rem; }
        .question { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9; }
        .question:last-child { border-bottom: none; }
        .q-label { flex: 1; font-size: 0.9375rem; color: #334155; font-weight: 500; }
        .badge { font-size: 0.8125rem; font-weight: 600; padding: 0.35rem 0.65rem; border-radius: 9999px; }
        .badge-yes { background: #dcfce7; color: #166534; }
        .badge-no { background: #fee2e2; color: #991b1b; }
        .section-title { font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem; }
        dl { margin: 0; }
        dt { font-size: 0.75rem; font-weight: 500; color: #64748b; margin-top: 0.5rem; }
        dd { font-size: 0.9375rem; color: #0f172a; margin: 0.15rem 0 0 0; }
        .brand { font-size: 0.8rem; color: #94a3b8; text-align: center; margin-top: 1.5rem; }
        .checklist { width: 100%; border-collapse: collapse; font-size: 0.875rem; margin-top: 0.5rem; }
        .checklist th, .checklist td { text-align: left; padding: 0.4rem 0.35rem; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .checklist th { color: #64748b; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .inspection-block { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; }
        .inspection-block:first-of-type { margin-top: 0.5rem; border-top: none; padding-top: 0; }
        .inspection-h { font-size: 0.85rem; font-weight: 600; color: #0f172a; margin-bottom: 0.35rem; }
        .inspection-meta { font-size: 0.8rem; color: #64748b; margin-bottom: 0.5rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('Meat traceability') }}</h1>
        <p class="subtitle">{{ __('Certificate') }}: {{ $certificateNumber }}</p>
        <div style="margin-bottom: 1rem;">
            <a href="{{ route('traceability.pdf', $certificateQr->slug) }}" style="display:inline-block;background:#0f766e;color:#fff;text-decoration:none;padding:0.45rem 0.75rem;border-radius:0.5rem;font-size:0.8rem;font-weight:600;">
                {{ __('Export PDF') }}
            </a>
        </div>

        {{-- Purpose of QR: instant answers --}}
        <p class="section-title">{{ __('Quick answers') }}</p>
        <div class="question">
            <span class="q-label">{{ __('Legally inspected?') }}</span>
            <span class="badge {{ $legallyInspected ? 'badge-yes' : 'badge-no' }}">{{ $legallyInspected ? __('Yes') : __('No') }}</span>
        </div>
        <div class="question">
            <span class="q-label">{{ __('Certificate valid?') }}</span>
            <span class="badge {{ $certificateValid ? 'badge-yes' : 'badge-no' }}">{{ $certificateValid ? __('Yes') : __('No') }}</span>
        </div>
        <div class="question">
            <span class="q-label">{{ __('Safe for sale?') }}</span>
            <span class="badge {{ $safeForSale ? 'badge-yes' : 'badge-no' }}">{{ $safeForSale ? __('Yes') : __('No') }}</span>
        </div>
    </div>

    <div class="card">
        <p class="section-title">{{ __('Where did it come from?') }}</p>
        <dl>
            <div>
                <dt>{{ __('Slaughter facility') }}</dt>
                <dd>{{ $facilityName }}</dd>
            </div>
            <div>
                <dt>{{ __('Slaughter date') }}</dt>
                <dd>{{ $slaughterDate }}</dd>
            </div>
            <div>
                <dt>{{ __('Batch') }}</dt>
                <dd>{{ $batchCode }}</dd>
            </div>
            <div>
                <dt>{{ __('Farm location') }}</dt>
                <dd>{{ $originLocation ?: '—' }}</dd>
            </div>
        </dl>
    </div>

    <div class="card">
        <p class="section-title">{{ __('Who inspected it?') }}</p>
        <dl>
            <div>
                <dt>{{ __('Inspector (certificate)') }}</dt>
                <dd>{{ $inspectorName }}</dd>
            </div>
        </dl>
    </div>

    @if ($animalIntake)
        <div class="card">
            <p class="section-title">{{ __('Animal origin information') }}</p>
            <dl>
                <div>
                    <dt>{{ __('Supplier') }}</dt>
                    <dd>{{ $animalIntake->supplier_firstname }} {{ $animalIntake->supplier_lastname }}</dd>
                </div>
                @if ($animalIntake->farm_name)
                    <div>
                        <dt>{{ __('Farm name') }}</dt>
                        <dd>{{ $animalIntake->farm_name }}</dd>
                    </div>
                @endif
                <div>
                    <dt>{{ __('Farm location') }}</dt>
                    <dd>{{ $originLocation ?: '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('Species') }}</dt>
                    <dd>{{ $animalIntake->species }}</dd>
                </div>
                <div>
                    <dt>{{ __('Number of animals (intake)') }}</dt>
                    <dd>{{ $animalIntake->number_of_animals }}</dd>
                </div>
                @if ($animalIntake->animal_health_certificate_number)
                    <div>
                        <dt>{{ __('Animal health certificate') }}</dt>
                        <dd>{{ $animalIntake->animal_health_certificate_number }}</dd>
                        @if ($animalIntake->health_certificate_expiry_date)
                            <dt style="margin-top: 0.25rem;">{{ __('Expiry') }}</dt>
                            <dd>{{ $animalIntake->health_certificate_expiry_date->format('d M Y') }}</dd>
                        @endif
                    </div>
                @endif
            </dl>
        </div>
    @endif

    @if (!empty($anteMortemInspectionsDetail))
        <div class="card">
            <p class="section-title">{{ __('Ante-mortem inspection — checklist') }}</p>
            @foreach ($anteMortemInspectionsDetail as $idx => $am)
                <div class="inspection-block">
                    <p class="inspection-h">{{ __('Record') }} {{ $idx + 1 }} — {{ $am['inspection_date'] }}</p>
                    <p class="inspection-meta">
                        {{ __('Species') }}: {{ $am['species'] }}
                        @if (!empty($am['inspector'])) · {{ __('Inspector') }}: {{ $am['inspector'] }} @endif
                    </p>
                    <p class="inspection-meta">
                        {{ __('Examined / approved / rejected') }}:
                        {{ $am['number_examined'] }} / {{ $am['number_approved'] }} / {{ $am['number_rejected'] }}
                    </p>
                    @if (!empty($am['notes']))
                        <p class="inspection-meta" style="white-space: pre-wrap;">{{ $am['notes'] }}</p>
                    @endif
                    @if (empty($am['rows']))
                        <p style="font-size: 0.875rem; color: #64748b;">{{ __('No checklist line items recorded.') }}</p>
                    @else
                        <table class="checklist" role="presentation">
                            <thead>
                                <tr>
                                    <th>{{ __('Item') }}</th>
                                    <th>{{ __('Result') }}</th>
                                    <th>{{ __('Notes') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($am['rows'] as $row)
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td>{{ $row['value'] }}</td>
                                        <td>{{ $row['notes'] ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if (!empty($postMortemInspectionDetail))
        @php $pm = $postMortemInspectionDetail; @endphp
        <div class="card">
            <p class="section-title">{{ __('Post-mortem inspection — checklist') }}</p>
            <p class="inspection-h">{{ $pm['inspection_date'] }} · {{ $pm['result'] }}</p>
            <p class="inspection-meta">
                {{ __('Species') }}: {{ $pm['species'] }}
                @if (!empty($pm['inspector'])) · {{ __('Inspector') }}: {{ $pm['inspector'] }} @endif
            </p>
            <p class="inspection-meta">
                {{ __('Total examined / approved / condemned') }}:
                {{ $pm['total_examined'] }} / {{ $pm['approved_quantity'] }} / {{ $pm['condemned_quantity'] }}
            </p>
            @if (!empty($pm['notes']))
                <p class="inspection-meta" style="white-space: pre-wrap;">{{ $pm['notes'] }}</p>
            @endif

            <p style="font-size: 0.8rem; font-weight: 600; color: #334155; margin: 0.75rem 0 0.35rem;">{{ __('Carcass inspection') }}</p>
            @if (empty($pm['carcass_rows']))
                <p style="font-size: 0.875rem; color: #64748b;">{{ __('No carcass observations recorded.') }}</p>
            @else
                <table class="checklist" role="presentation">
                    <thead>
                        <tr>
                            <th>{{ __('Item') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pm['carcass_rows'] as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td>{{ $row['value'] }}</td>
                                <td>{{ $row['notes'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <p style="font-size: 0.8rem; font-weight: 600; color: #334155; margin: 0.75rem 0 0.35rem;">{{ __('Organ inspection') }}</p>
            @if (empty($pm['organ_rows']))
                <p style="font-size: 0.875rem; color: #64748b;">{{ __('No organ observations recorded.') }}</p>
            @else
                <table class="checklist" role="presentation">
                    <thead>
                        <tr>
                            <th>{{ __('Item') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pm['organ_rows'] as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td>{{ $row['value'] }}</td>
                                <td>{{ $row['notes'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif

    <p class="brand">BuchaPro — {{ __('Meat traceability') }}</p>
</body>
</html>
