<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Transport trip') }} — {{ $trip->vehicle_plate_number }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('transport-trips.edit', $trip) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('certificates.show', $trip->certificate) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('View certificate') }}
                </a>
                <a href="{{ route('transport-trips.index') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                    {{ __('Back to list') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Certificate') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('certificates.show', $trip->certificate) }}" class="text-indigo-600 hover:underline">
                                {{ $trip->certificate->certificate_number ?: '#' . $trip->certificate_id }}
                            </a>
                        </dd>
                    </div>
                    @if ($trip->batch)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Batch') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="{{ route('batches.show', $trip->batch) }}" class="text-indigo-600 hover:underline">{{ $trip->batch->batch_code }}</a>
                            </dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Vehicle plate number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $trip->vehicle_plate_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Driver name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $trip->driver_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Driver phone') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $trip->driver_phone ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Origin facility') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $trip->originFacility ? $trip->originFacility->facility_name : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Destination facility') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $trip->destinationFacility ? $trip->destinationFacility->facility_name : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Departure date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $trip->departure_date->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Arrival date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">@if ($trip->arrival_date){{ $trip->arrival_date->format('d M Y') }}@else—@endif</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $trip->status)) }}</dd>
                    </div>
                </dl>
            </div>

            @if ($trip->deliveryConfirmation)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Delivery confirmation') }}</h3>
                    <p class="text-sm text-gray-600 mb-2">{{ $trip->deliveryConfirmation->receivingFacility ? $trip->deliveryConfirmation->receivingFacility->facility_name : '' }} · {{ $trip->deliveryConfirmation->received_quantity }} {{ __('received') }} · {{ $trip->deliveryConfirmation->received_date->format('d M Y') }} · {{ ucfirst($trip->deliveryConfirmation->confirmation_status) }}</p>
                    <a href="{{ route('delivery-confirmations.show', $trip->deliveryConfirmation) }}" class="text-sm text-indigo-600 hover:underline">{{ __('View confirmation') }}</a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Delivery confirmation') }}</h3>
                    <p class="text-sm text-gray-500 mb-2">{{ __('No delivery confirmation recorded for this trip.') }}</p>
                    <a href="{{ route('delivery-confirmations.create') }}" class="text-sm text-indigo-600 hover:underline">{{ __('Confirm delivery') }}</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
