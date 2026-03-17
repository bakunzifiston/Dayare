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
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('Meat traceability') }}</h1>
        <p class="subtitle">{{ __('Certificate') }}: {{ $certificateNumber }}</p>

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
            @if ($originLocation)
                <div>
                    <dt>{{ __('Origin (farm / location)') }}</dt>
                    <dd>{{ $originLocation }}</dd>
                </div>
            @endif
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
                @if ($originLocation)
                    <div>
                        <dt>{{ __('Origin location') }}</dt>
                        <dd>{{ $originLocation }}</dd>
                    </div>
                @endif
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

    <p class="brand">BuchaPro — {{ __('Meat traceability') }}</p>
</body>
</html>
