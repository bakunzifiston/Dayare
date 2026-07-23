<x-app-layout>
    @push('styles')
        <style>
            @media print {
                @page { margin: 12mm; size: A4 portrait; }
                header, nav, .no-print { display: none !important; }
                body { background: #fff !important; }
                .rica-report { box-shadow: none !important; }
            }
        </style>
    @endpush

    <x-slot name="header">
        <div class="no-print flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('certificates.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Certificates') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Certificate') }} — {{ $certificateNumber }}
                </h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('certificates.edit', $certificate) }}#pdf-details" class="rica-btn rica-btn--secondary">{{ __('Edit PDF details') }}</a>
                <a href="{{ route('certificates.export-single', $certificate) }}" class="rica-btn rica-btn--primary">{{ __('Download PDF') }}</a>
                @if ($certificate->certificateQr)
                    <a href="{{ $certificate->certificateQr->trace_url }}" target="_blank" rel="noopener" class="rica-btn rica-btn--secondary">{{ __('Open trace page') }}</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="rica-report-page cert-trace-page max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->has('certificate_pdf'))
                <div class="no-print mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first('certificate_pdf') }}
                </div>
            @endif

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

            <article class="rica-report print-card">
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
                        <span class="rica-status rica-status--{{ $certificate->status === 'active' ? 'submitted' : 'pending' }}">{{ ucfirst($certificate->status) }}</span>
                    </div>
                    <div class="rica-report__cover-dates">
                        @if ($certificateView['issuedAtFormatted'])
                            <span><strong>Tariki</strong>: {{ $certificateView['issuedAtFormatted'] }}</span>
                        @endif
                        @if ($certificate->expiry_date)
                            <span><strong>{{ __('Expires') }}</strong>: {{ $certificate->expiry_date->format('d/m/Y') }}</span>
                        @endif
                    </div>
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

            @if ($certificate->batch?->hasPerAnimalData())
                <details class="rica-report mt-4 no-print" open>
                    <summary class="cursor-pointer px-4 py-3 sm:px-6 text-sm font-semibold text-emerald-900 border-b border-emerald-100 bg-emerald-50/80">
                        {{ __('Animals on this certificate') }}
                    </summary>
                    <div class="rica-report__body">
                        <div class="rica-table-wrap">
                            <table class="rica-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('Ear tag') }}</th>
                                        <th>{{ __('Species') }}</th>
                                        <th>{{ __('Batch meat qty') }}</th>
                                        <th>{{ __('Carcass weight') }}</th>
                                        <th>{{ __('Released (kg)') }}</th>
                                        <th>{{ __('Cold room') }}</th>
                                        <th>{{ __('PM outcome') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($certificate->batch->items as $batchItem)
                                        @php
                                            if ($certificateAnimalIds->isNotEmpty() && ! $certificateAnimalIds->contains((int) $batchItem->animal_intake_item_id)) {
                                                continue;
                                            }
                                            $pm = $batchItem->postMortemOutcome;
                                            $intake = $batchItem->intakeItem;
                                            $animalStorage = ($releaseByAnimalId ?? collect())->get($intake->id);
                                            $pmBadge = match ($pm?->outcome) {
                                                'approved' => 'rica-status--submitted',
                                                'condemned' => 'bg-red-100 text-red-800',
                                                'deferred' => 'rica-status--draft',
                                                default => 'rica-status--pending',
                                            };
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td class="font-mono text-xs">{{ $intake->ear_tag }}</td>
                                            <td>{{ $intake->species }}</td>
                                            <td class="tabular-nums">{{ number_format($batchItem->meat_quantity_kg, 2) }} kg</td>
                                            <td class="tabular-nums">
                                                @php
                                                    $carcassKg = $pm?->displayCarcassWeightKg();
                                                @endphp
                                                {{ $carcassKg !== null ? number_format($carcassKg, 2).' kg' : '—' }}
                                            </td>
                                            <td class="tabular-nums">
                                                @if ($animalStorage?->isReleased())
                                                    {{ number_format((float) $animalStorage->quantity_stored, 2) }} kg
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                <span class="rica-status {{ $animalStorage?->isReleased() ? 'rica-status--submitted' : ($animalStorage ? 'rica-status--draft' : 'rica-status--pending') }}">
                                                    {{ $animalStorage ? ucfirst(str_replace('_', ' ', $animalStorage->status)) : __('Not stored') }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="rica-status {{ $pmBadge }}">{{ $pm ? ucfirst($pm->outcome) : __('Not recorded') }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>
            @endif

            @if ($certificate->certificateQr)
                <div class="rica-report mt-4">
                    <div class="rica-report__body">
                        <h3 class="rica-section-title">{{ __('QR traceability') }}</h3>
                        <p class="text-xs text-slate-600 mb-4">{{ __('Scan to open the public certificate verification page.') }}</p>
                        <div class="flex flex-wrap items-start gap-6">
                            <img src="{{ route('certificates.qr', $certificate) }}" alt="QR Code" class="w-36 h-36 border border-emerald-100 rounded-lg p-2 bg-white" width="144" height="144">
                            <div class="min-w-0 flex-1 space-y-2">
                                <a href="{{ $certificate->certificateQr->trace_url }}" target="_blank" rel="noopener" class="text-sm font-medium text-emerald-800 hover:underline break-all">{{ $certificate->certificateQr->trace_url }}</a>
                                <div class="no-print flex flex-wrap gap-2">
                                    <a href="{{ route('traceability.pdf', $certificate->certificateQr->slug) }}" class="rica-btn rica-btn--secondary text-xs">{{ __('Export trace PDF') }}</a>
                                    <button type="button" onclick="window.print()" class="rica-btn rica-btn--secondary text-xs">{{ __('Print') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($certificate->transportTrips->isNotEmpty())
                <div class="rica-report mt-4 no-print">
                    <div class="rica-report__body">
                        <h3 class="rica-section-title">{{ __('Transport trips') }}</h3>
                        <div class="rica-table-wrap">
                            <table class="rica-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Vehicle') }}</th>
                                        <th>{{ __('Driver') }}</th>
                                        <th>{{ __('Route') }}</th>
                                        <th>{{ __('Departure') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($certificate->transportTrips as $tr)
                                        <tr>
                                            <td>
                                                <a href="{{ route('transport-trips.show', $tr) }}" class="font-medium text-emerald-800 hover:underline">{{ $tr->vehicle_plate_number }}</a>
                                            </td>
                                            <td>{{ $tr->driver_name }}</td>
                                            <td>{{ $tr->originFacility->facility_name ?? '—' }} → {{ $tr->destination_display }}</td>
                                            <td>{{ $tr->departure_date->format('d M Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <a href="{{ route('transport-trips.create', ['certificate_id' => $certificate->id]) }}" class="inline-block mt-3 text-sm font-medium text-emerald-800 hover:underline">{{ __('Record another trip') }}</a>
                    </div>
                </div>
            @else
                <div class="rica-index-hint mt-4 no-print">
                    {{ __('No transport trip recorded yet.') }}
                    <a href="{{ route('transport-trips.create', ['certificate_id' => $certificate->id]) }}" class="font-medium text-emerald-800 hover:underline">{{ __('Record trip') }}</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
