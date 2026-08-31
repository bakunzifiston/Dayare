<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Transport') }}</span>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $trip->vehicle_plate_number }}</p>
                    <p class="text-xs text-slate-500">{{ $trip->driver_name }} · {{ ucfirst(str_replace('_', ' ', $trip->status)) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('transport-trips.edit', $trip) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Edit') }}</a>
                    <a href="{{ route('certificates.show', $trip->certificate) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Certificate') }}</a>
                    <a href="{{ route('transport-trips.hub') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Certificate') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        <a href="{{ route('certificates.show', $trip->certificate) }}" class="text-bucha-primary hover:underline">
                            {{ $trip->certificate->certificate_number ?: '#' . $trip->certificate_id }}
                        </a>
                    </dd>
                </div>
                @if ($trip->batch)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Batch') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">
                            <a href="{{ route('batches.show', $trip->batch) }}" class="text-bucha-primary hover:underline">{{ $trip->batch->batch_code }}</a>
                        </dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Vehicle plate number') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $trip->vehicle_plate_number }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Driver name') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $trip->driver_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Driver phone') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $trip->driver_phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Origin facility') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $trip->originFacility ? $trip->originFacility->facility_name : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Destination') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        @if ($trip->isExternalDestination())
                            <span class="text-slate-600">{{ __('Other place') }}</span> — {{ $trip->destination_display }}
                            @if ($trip->destination_address)
                                <span class="mt-0.5 block text-xs text-slate-500">{{ $trip->destination_address }}</span>
                            @endif
                        @else
                            {{ $trip->destination_display }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Departure date') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $trip->departure_date->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Arrival date') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">@if ($trip->arrival_date){{ $trip->arrival_date->format('d M Y') }}@else—@endif</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Status') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ ucfirst(str_replace('_', ' ', $trip->status)) }}</dd>
                </div>
            </dl>
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Delivery confirmation') }}</p>
            </div>
            <div class="px-4 py-4">
                @if ($trip->deliveryConfirmation)
                    <p class="text-sm text-slate-600">
                        {{ $trip->deliveryConfirmation->receiver_display }}
                        · {{ $trip->deliveryConfirmation->received_quantity }} {{ $trip->deliveryConfirmation->received_unit ?? 'units' }}
                        · {{ $trip->deliveryConfirmation->received_date->format('d M Y') }}
                        · {{ ucfirst($trip->deliveryConfirmation->confirmation_status) }}
                    </p>
                    <a href="{{ route('delivery-confirmations.show', $trip->deliveryConfirmation) }}" class="mt-2 inline-flex text-xs font-medium text-bucha-primary hover:underline">{{ __('View confirmation') }}</a>
                @else
                    <p class="text-sm text-slate-500">{{ __('Record trip only logs the movement. Add received quantity, unit, contract, and international export documents on the delivery confirmation.') }}</p>
                    <a href="{{ route('delivery-confirmations.create', ['transport_trip_id' => $trip->id]) }}" class="mt-3 inline-flex h-8 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Confirm delivery') }}</a>
                @endif
            </div>
        </section>
    </div>
</x-app-layout>
