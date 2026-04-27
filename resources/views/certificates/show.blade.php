<x-app-layout>
    @push('styles')
        <style>
            @media print {
                @page {
                    margin: 14mm;
                    size: A4 portrait;
                }

                body {
                    background: #fff !important;
                    color: #111827 !important;
                    font-size: 12px;
                    line-height: 1.35;
                }

                header,
                nav,
                .no-print {
                    display: none !important;
                }

                .print-card {
                    box-shadow: none !important;
                    border: 1px solid #d1d5db !important;
                    border-radius: 10px !important;
                    padding: 14px !important;
                    break-inside: avoid;
                    page-break-inside: avoid;
                }

                .print-title {
                    font-size: 20px !important;
                    font-weight: 700 !important;
                    color: #111827 !important;
                    margin-bottom: 4px !important;
                }

                .print-subtitle {
                    font-size: 11px !important;
                    color: #6b7280 !important;
                    margin-bottom: 10px !important;
                }

                .print-qr {
                    width: 150px !important;
                    height: 150px !important;
                }

                a {
                    color: inherit !important;
                    text-decoration: none !important;
                }
            }
        </style>
    @endpush

    <x-slot name="header">
        <div class="no-print flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('certificates.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Certificates') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Certificate') }} — {{ $certificate->certificate_number ?: '#' . $certificate->id }}
                </h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('certificates.edit', $certificate) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                <button type="button" onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Print') }}
                </button>
                <a href="{{ route('certificates.export-single', $certificate) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Export PDF') }}
                </a>
                @if ($certificate->batch)
                    <a href="{{ route('batches.show', $certificate->batch) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                        {{ __('View batch') }}
                    </a>
                @endif
                <a href="{{ route('certificates.index') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('All certificates') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @php
                $plan = $certificate->batch?->slaughterExecution?->slaughterPlan;
                $animalIntake = $plan?->animalIntake;
                $anteMortemCount = $plan?->anteMortemInspections?->count() ?? 0;
                $anteMortemApproved = $plan?->anteMortemInspections?->sum('number_approved') ?? 0;
                $anteMortemRejected = $plan?->anteMortemInspections?->sum('number_rejected') ?? 0;
                $postMortem = $certificate->batch?->postMortemInspection;
                $originLocationParts = array_filter([
                    $animalIntake?->village?->name,
                    $animalIntake?->cell?->name,
                    $animalIntake?->sector?->name,
                    $animalIntake?->district?->name,
                    $animalIntake?->province?->name,
                    $animalIntake?->country?->name,
                ]);
                $originLocation = !empty($originLocationParts) ? implode(', ', $originLocationParts) : '—';
                $originSourceName = trim((string) (($animalIntake?->supplier_firstname ?? '').' '.($animalIntake?->supplier_lastname ?? '')));
            @endphp

            <div class="print-card bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h1 class="print-title">{{ __('Meat Inspection Certificate') }}</h1>
                <p class="print-subtitle">{{ __('Certificate') }} — {{ $certificate->certificate_number ?: '#' . $certificate->id }}</p>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @if ($certificate->batch)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Batch') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="{{ route('batches.show', $certificate->batch) }}" class="text-bucha-primary hover:underline">
                                    {{ $certificate->batch->batch_code }}
                                </a>
                            </dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Certificate number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $certificate->certificate_number ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Inspector') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('inspectors.show', $certificate->inspector) }}" class="text-bucha-primary hover:underline">
                                {{ $certificate->inspector->full_name }}
                            </a>
                        </dd>
                    </div>
                    @if ($certificate->facility)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Facility') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $certificate->facility->facility_name }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Issue date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $certificate->issued_at?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Expiry date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $certificate->expiry_date?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($certificate->status) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="print-card bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Inspection and animal origin') }}</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Ante-Mortem inspection') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if ($anteMortemCount > 0)
                                {{ __('Completed (:count records)', ['count' => $anteMortemCount]) }}
                                <span class="block text-xs text-gray-500">{{ __('Approved: :approved, Rejected: :rejected', ['approved' => $anteMortemApproved, 'rejected' => $anteMortemRejected]) }}</span>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Post-Mortem inspection') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if ($postMortem)
                                {{ ucfirst((string) ($postMortem->result ?? '—')) }}
                                <span class="block text-xs text-gray-500">{{ __('Approved quantity: :qty', ['qty' => $postMortem->approved_quantity ?? 0]) }}</span>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Animal origin (farm)') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $animalIntake?->farm_name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Animal origin (farmer/supplier)') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $originSourceName !== '' ? $originSourceName : '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Farm location') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $originLocation }}</dd>
                    </div>
                </dl>
            </div>

            @if ($certificate->certificateQr)
                <div class="print-card bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('QR traceability') }}</h3>
                    <p class="text-sm text-gray-500 mb-3">{{ __('Scan the QR code or open the link to view traceability data (facility, inspector, slaughter date, batch, certificate).') }}</p>
                    <div class="flex flex-wrap items-center gap-6">
                        <img src="{{ route('certificates.qr', $certificate) }}" alt="QR Code" class="print-qr w-48 h-48" width="200" height="200">
                        <div>
                            <a href="{{ $certificate->certificateQr->trace_url }}" target="_blank" rel="noopener" class="text-bucha-primary hover:underline break-all">{{ $certificate->certificateQr->trace_url }}</a>
                        </div>
                    </div>
                </div>
            @endif

            @if ($certificate->transportTrips->isNotEmpty())
                <div class="print-card bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Transport trips') }}</h3>
                    <ul class="divide-y divide-gray-200">
                        @foreach ($certificate->transportTrips as $tr)
                            <li class="py-2">
                                <a href="{{ route('transport-trips.show', $tr) }}" class="font-medium text-bucha-primary hover:underline">{{ $tr->vehicle_plate_number }}</a>
                                <span class="text-sm text-gray-500"> {{ $tr->driver_name }} · {{ $tr->originFacility->facility_name ?? '' }} → {{ $tr->destinationFacility->facility_name ?? '' }} · {{ $tr->departure_date->format('d M Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('transport-trips.create') }}" class="no-print inline-flex items-center mt-2 text-sm text-bucha-primary hover:text-indigo-900">{{ __('Record trip') }}</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
