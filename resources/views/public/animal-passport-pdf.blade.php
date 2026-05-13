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
        table.data { width: 100%; border-collapse: collapse; font-size: 8px; margin-top: 4px; }
        table.data th, table.data td { border: 1px solid #cbd5e1; padding: 4px 5px; text-align: left; vertical-align: top; }
        table.data th { background: #f1f5f9; font-weight: bold; }
        .muted { color: #64748b; font-size: 9px; }
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

    <p class="muted" style="margin-top: 10px;">{{ __('Vaccinations, treatments, disease records, and vet visits below include up to :max of the most recent entries per category.', ['max' => $healthRecordLimit]) }}</p>

    <div class="section">
        <h3>{{ __('Vaccinations') }}</h3>
        @if ($vaccinations->isEmpty())
            <p class="muted">{{ __('No vaccination records.') }}</p>
        @else
            <p class="muted">{{ __(':count record(s) on this document.', ['count' => $vaccinations->count()]) }}</p>
            <table class="data">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Vaccine') }}</th>
                        <th>{{ __('Batch') }}</th>
                        <th>{{ __('Manufacturer') }}</th>
                        <th>{{ __('Dosage') }}</th>
                        <th>{{ __('Next due') }}</th>
                        <th>{{ __('Veterinarian') }}</th>
                        <th>{{ __('Clinic') }}</th>
                        <th>{{ __('Administered by') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Notes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vaccinations as $v)
                        <tr>
                            <td>{{ $v->vaccination_date?->toDateString() ?: '—' }}</td>
                            <td>{{ $v->vaccination_code ?: '—' }}</td>
                            <td>{{ $v->vaccine_name ?: '—' }}@if($v->vaccine_type)<br><span class="muted">{{ $v->vaccine_type }}</span>@endif</td>
                            <td>{{ $v->batch_number ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($v->manufacturer ?? ''), 24) ?: '—' }}</td>
                            <td>{{ $v->dosage ?: '—' }}</td>
                            <td>{{ $v->next_due_date?->toDateString() ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($v->veterinarian_name ?? ''), 20) ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($v->veterinary_clinic ?? ''), 20) ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($v->administered_by ?? ''), 18) ?: '—' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', (string) $v->status)) }}</td>
                            <td>{{ \Illuminate\Support\Str::limit(trim((string) ($v->notes ?? '').($v->reaction_notes ? ' '.$v->reaction_notes : '')), 80) ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="section">
        <h3>{{ __('Treatments') }}</h3>
        @if ($treatments->isEmpty())
            <p class="muted">{{ __('No treatment records.') }}</p>
        @else
            <p class="muted">{{ __(':count record(s) on this document.', ['count' => $treatments->count()]) }}</p>
            <table class="data">
                <thead>
                    <tr>
                        <th>{{ __('Start') }}</th>
                        <th>{{ __('End') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Disease / focus') }}</th>
                        <th>{{ __('Symptoms') }}</th>
                        <th>{{ __('Medicine') }}</th>
                        <th>{{ __('Dosage') }}</th>
                        <th>{{ __('Method') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Response') }}</th>
                        <th>{{ __('Follow-up') }}</th>
                        <th>{{ __('Veterinarian') }}</th>
                        <th>{{ __('Notes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($treatments as $t)
                        <tr>
                            <td>{{ $t->treatment_start_date?->toDateString() ?: '—' }}</td>
                            <td>{{ $t->treatment_end_date?->toDateString() ?: '—' }}</td>
                            <td>{{ $t->treatment_code ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($t->disease_name ?: $t->diagnosis ?: '—'), 40) }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($t->symptoms ?? ''), 50) ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($t->medicine_name ?? ''), 28) ?: '—' }}</td>
                            <td>{{ $t->dosage ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($t->treatment_method ?? ''), 20) ?: '—' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', (string) $t->status)) }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($t->response_to_treatment ?? ''), 35) ?: '—' }}</td>
                            <td>{{ $t->follow_up_date?->toDateString() ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($t->veterinarian_name ?? ''), 18) ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($t->notes ?? ''), 60) ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="section">
        <h3>{{ __('Disease records') }}</h3>
        @if ($diseaseRecords->isEmpty())
            <p class="muted">{{ __('No disease records.') }}</p>
        @else
            <p class="muted">{{ __(':count record(s) on this document.', ['count' => $diseaseRecords->count()]) }}</p>
            <table class="data">
                <thead>
                    <tr>
                        <th>{{ __('Diagnosis date') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Disease') }}</th>
                        <th>{{ __('Symptoms') }}</th>
                        <th>{{ __('Severity') }}</th>
                        <th>{{ __('Recovery') }}</th>
                        <th>{{ __('Contagious') }}</th>
                        <th>{{ __('Quarantine') }}</th>
                        <th>{{ __('Veterinarian') }}</th>
                        <th>{{ __('Notes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($diseaseRecords as $d)
                        <tr>
                            <td>{{ $d->diagnosis_date?->toDateString() ?: '—' }}</td>
                            <td>{{ $d->disease_code ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($d->disease_name ?? ''), 35) ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($d->symptoms ?? ''), 55) ?: '—' }}</td>
                            <td>{{ ucfirst((string) $d->severity_level) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', (string) $d->recovery_status)) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', (string) $d->contagious_status)) }}</td>
                            <td>{{ $d->quarantine_required ? __('Yes') : __('No') }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($d->veterinarian_name ?? ''), 18) ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($d->notes ?? ''), 70) ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="section">
        <h3>{{ __('Veterinary visits') }}</h3>
        @if ($veterinaryVisits->isEmpty())
            <p class="muted">{{ __('No veterinary visit records.') }}</p>
        @else
            <p class="muted">{{ __(':count record(s) on this document.', ['count' => $veterinaryVisits->count()]) }}</p>
            <table class="data">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Veterinarian') }}</th>
                        <th>{{ __('Clinic') }}</th>
                        <th>{{ __('Purpose') }}</th>
                        <th>{{ __('Findings') }}</th>
                        <th>{{ __('Recommendations') }}</th>
                        <th>{{ __('Follow-up') }}</th>
                        <th>{{ __('Notes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($veterinaryVisits as $visit)
                        <tr>
                            <td>{{ $visit->visit_date?->toDateString() ?: '—' }}</td>
                            <td>{{ $visit->visit_code ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($visit->veterinarian_name ?? ''), 18) ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($visit->clinic_name ?? ''), 18) ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($visit->purpose_of_visit ?? ''), 55) ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($visit->findings ?? ''), 70) ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($visit->recommendations ?? ''), 70) ?: '—' }}</td>
                            <td>
                                @if ($visit->follow_up_required)
                                    {{ __('Yes') }}@if($visit->follow_up_date) ({{ $visit->follow_up_date->toDateString() }})@endif
                                @else
                                    {{ __('No') }}
                                @endif
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($visit->notes ?? ''), 70) ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="footer">{{ __('This document is generated from BuchaPro records. Verify the latest status using the QR code or your official verification link.') }}</div>
</body>
</html>
