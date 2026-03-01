<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Transport trips') }}
            </h2>
            <a href="{{ route('transport-trips.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                {{ __('Record trip') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($trips->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 text-center text-gray-600">
                    <p class="mb-4">{{ __('No transport trips recorded yet.') }}</p>
                    <a href="{{ route('transport-trips.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                        {{ __('Record first trip') }}
                    </a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <ul class="divide-y divide-gray-200">
                        @foreach ($trips as $trip)
                            <li class="p-4 flex justify-between items-center hover:bg-gray-50">
                                <div>
                                    <a href="{{ route('transport-trips.show', $trip) }}" class="font-medium text-gray-900 hover:underline">
                                        {{ $trip->vehicle_plate_number }} — {{ $trip->driver_name }}
                                    </a>
                                    <p class="text-sm text-gray-500">
                                        {{ $trip->originFacility->facility_name ?? '' }} → {{ $trip->destinationFacility->facility_name ?? '' }}
                                        · {{ $trip->departure_date->format('d M Y') }} · {{ ucfirst(str_replace('_', ' ', $trip->status)) }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ __('Certificate') }}: {{ $trip->certificate->certificate_number ?: '#' . $trip->certificate_id }}
                                    </p>
                                </div>
                                <a href="{{ route('transport-trips.edit', $trip) }}" class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="p-4 border-t">
                        {{ $trips->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
