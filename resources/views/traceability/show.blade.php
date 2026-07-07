<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Veterinary meat inspection certificate') }} — {{ $certificateNumber }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 min-h-screen py-6 px-4 sm:px-6">
    <div class="rica-report-page cert-trace-page max-w-3xl">
        <div class="rica-toolbar">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-emerald-800">{{ __('Verified certificate') }}</p>
                <p class="text-sm text-slate-600">{{ __('Scan result') }} · {{ $certificateNumber }}</p>
            </div>
            <div class="rica-toolbar__actions">
                <a href="{{ route('traceability.pdf', $certificateQr->slug) }}" class="rica-btn rica-btn--secondary">
                    {{ __('Export PDF') }}
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-4">
            <div class="rounded-lg border px-3 py-2.5 text-center {{ $legallyInspected ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }}">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ __('Legally inspected') }}</p>
                <p class="text-sm font-bold {{ $legallyInspected ? 'text-emerald-800' : 'text-red-800' }}">{{ $legallyInspected ? __('Yes') : __('No') }}</p>
            </div>
            <div class="rounded-lg border px-3 py-2.5 text-center {{ $certificateValid ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }}">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ __('Certificate valid') }}</p>
                <p class="text-sm font-bold {{ $certificateValid ? 'text-emerald-800' : 'text-red-800' }}">{{ $certificateValid ? __('Yes') : __('No') }}</p>
            </div>
            <div class="rounded-lg border px-3 py-2.5 text-center {{ $safeForSale ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }}">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ __('Safe for sale') }}</p>
                <p class="text-sm font-bold {{ $safeForSale ? 'text-emerald-800' : 'text-red-800' }}">{{ $safeForSale ? __('Yes') : __('No') }}</p>
            </div>
        </div>

        <article class="rica-report">
            <header class="rica-report__cover">
                <div class="rica-report__cover-flag" aria-hidden="true">
                    <span class="flag-blue"></span>
                    <span class="flag-yellow"></span>
                    <span class="flag-green"></span>
                </div>
                <p class="rica-report__cover-republic">{{ __('REPUBLIC OF RWANDA') }}</p>
                <p class="rica-report__cover-republic-rw">Republika y'u Rwanda</p>
                <h1 class="rica-report__cover-title">{{ $certificateView['slaughterhouseDisplayName'] }}</h1>
                <p class="rica-report__cover-subtitle">INYITO: ICYEMEZO CYA VETERINERI KU BUGENZUZI BW'INYAMA</p>
                <div class="rica-report__cover-meta">
                    <span class="rica-report__badge">{{ $certificateView['headerDistrictLine'] }}</span>
                    <span class="rica-report__badge">{{ $certificateView['headerSectorLine'] }}</span>
                    <span class="rica-report__badge">{{ $certificateView['headerCellLine'] }}</span>
                    <span class="rica-report__badge rica-report__badge--highlight">{{ __('Certificate') }}: {{ $certificateNumber }}</span>
                </div>
                @if ($certificateView['issuedAtFormatted'])
                    <div class="rica-report__cover-dates">
                        <span><strong>Tariki</strong>: {{ $certificateView['issuedAtFormatted'] }}</span>
                        @if ($certificate->expiry_date)
                            <span><strong>{{ __('Expires') }}</strong>: {{ $certificate->expiry_date->format('d/m/Y') }}</span>
                        @endif
                    </div>
                @endif
            </header>

            <div class="rica-report__body">
                @include('traceability.partials.certificate-sections', [
                    'certificateView' => $certificateView,
                    'certificateNumber' => $certificateNumber,
                    'inspectorName' => $inspectorName,
                    'slaughterDate' => $slaughterDate,
                ])
            </div>
        </article>

        @if (! empty($animalsDetail))
            <details class="rica-report mt-4">
                <summary class="cursor-pointer px-4 py-3 sm:px-6 text-sm font-semibold text-emerald-900 border-b border-emerald-100 bg-emerald-50/80">
                    {{ __('Inspection traceability') }} ({{ count($animalsDetail) }} {{ __('animals') }})
                </summary>
                <div class="rica-report__body space-y-3">
                    @foreach ($animalsDetail as $animal)
                        <details class="rounded-lg border border-emerald-100 bg-white overflow-hidden">
                            <summary class="cursor-pointer px-3 py-2.5 text-sm font-medium text-slate-800">
                                <span class="font-mono text-emerald-800">{{ $animal['ear_tag'] }}</span>
                                · {{ $animal['species'] }} · {{ $animal['pm_outcome'] ?: __('Not recorded') }}
                            </summary>
                            <div class="px-3 pb-3 border-t border-slate-100">
                                @include('traceability.partials.animal-inspection-detail', ['animal' => $animal])
                            </div>
                        </details>
                    @endforeach
                </div>
            </details>
        @endif

        <p class="text-center text-xs text-slate-400 mt-6">
            {{ config('app.name', 'BuchaPro') }} · {{ __('Meat traceability') }}
        </p>
    </div>
</body>
</html>
