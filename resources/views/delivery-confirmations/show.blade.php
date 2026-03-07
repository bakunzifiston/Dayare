<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Delivery confirmation') }} — {{ $confirmation->transportTrip->vehicle_plate_number ?? '' }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('delivery-confirmations.edit', $confirmation) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('transport-trips.show', $confirmation->transportTrip) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('View trip') }}
                </a>
                <a href="{{ route('delivery-confirmations.index') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
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
                        <dt class="text-sm font-medium text-gray-500">{{ __('Transport trip') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('transport-trips.show', $confirmation->transportTrip) }}" class="text-indigo-600 hover:underline">
                                {{ $confirmation->transportTrip->vehicle_plate_number }} — {{ $confirmation->transportTrip->driver_name }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Receiving at') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if ($confirmation->receivingFacility)
                                {{ $confirmation->receivingFacility->facility_name }}
                            @else
                                <span class="text-gray-600">{{ __('External / non-registered') }}</span> — {{ $confirmation->receiver_name }}{!! $confirmation->receiver_country ? ' (' . e($confirmation->receiver_country) . ')' : '' !!}
                            @endif
                        </dd>
                    </div>
                    @if ($confirmation->client_id && $confirmation->client)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Client') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('clients.show', $confirmation->client) }}" class="text-indigo-600 hover:underline">{{ $confirmation->client->display_name }}</a>
                        </dd>
                    </div>
                    @endif
                    @if ($confirmation->receiver_address)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Receiver address') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $confirmation->receiver_address }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Received quantity') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $confirmation->received_quantity }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Received date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $confirmation->received_date->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Receiver name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $confirmation->receiver_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Confirmation status') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($confirmation->confirmation_status) }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
