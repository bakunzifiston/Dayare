<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Certificate') }} — {{ $certificate->certificate_number ?: '#' . $certificate->id }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('certificates.edit', $certificate) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                @if ($certificate->batch)
                    <a href="{{ route('batches.show', $certificate->batch) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                        {{ __('View batch') }}
                    </a>
                @endif
                <a href="{{ route('certificates.index') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                    {{ __('Back to list') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @if ($certificate->batch)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Batch') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="{{ route('batches.show', $certificate->batch) }}" class="text-indigo-600 hover:underline">
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
                            <a href="{{ route('inspectors.show', $certificate->inspector) }}" class="text-indigo-600 hover:underline">
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

            @if ($certificate->certificateQr)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('QR traceability') }}</h3>
                    <p class="text-sm text-gray-500 mb-3">{{ __('Scan the QR code or open the link to view traceability data (facility, inspector, slaughter date, batch, certificate).') }}</p>
                    <div class="flex flex-wrap items-center gap-6">
                        <img src="{{ route('certificates.qr', $certificate) }}" alt="QR Code" class="w-48 h-48" width="200" height="200">
                        <div>
                            <a href="{{ $certificate->certificateQr->trace_url }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline break-all">{{ $certificate->certificateQr->trace_url }}</a>
                        </div>
                    </div>
                </div>
            @endif

            @if ($certificate->transportTrips->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Transport trips') }}</h3>
                    <ul class="divide-y divide-gray-200">
                        @foreach ($certificate->transportTrips as $tr)
                            <li class="py-2">
                                <a href="{{ route('transport-trips.show', $tr) }}" class="font-medium text-indigo-600 hover:underline">{{ $tr->vehicle_plate_number }}</a>
                                <span class="text-sm text-gray-500"> {{ $tr->driver_name }} · {{ $tr->originFacility->facility_name ?? '' }} → {{ $tr->destinationFacility->facility_name ?? '' }} · {{ $tr->departure_date->format('d M Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('transport-trips.create') }}" class="inline-flex items-center mt-2 text-sm text-indigo-600 hover:text-indigo-900">{{ __('Record trip') }}</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
