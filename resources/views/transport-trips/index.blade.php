<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('transport-trips.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Transport') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                    {{ __('All trips') }}
                </h2>
            </div>
            <a href="{{ route('transport-trips.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Record trip') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-nowrap items-center gap-3 mb-6 overflow-x-auto pb-1 rounded-xl border border-slate-200/60 bg-white px-4 py-3 shadow-sm">
                <x-kpi-card inline title="{{ __('Total trips') }}" :value="$kpis['total']" color="blue" />
                <x-kpi-card inline title="{{ __('Arrived') }}" :value="$kpis['arrived']" color="green" />
                <x-kpi-card inline title="{{ __('Completed') }}" :value="$kpis['completed']" color="slate" />
            </div>
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if ($trips->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-8 text-center text-slate-600">
                    <p class="mb-4">{{ __('No transport trips recorded yet.') }}</p>
                    <a href="{{ route('transport-trips.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                        {{ __('Record first trip') }}
                    </a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <ul class="divide-y divide-slate-100">
                        @foreach ($trips as $trip)
                            <li class="p-4 flex justify-between items-center hover:bg-slate-50/80 transition-colors">
                                <div>
                                    <a href="{{ route('transport-trips.show', $trip) }}" class="font-medium text-slate-900 hover:text-bucha-primary">
                                        {{ $trip->vehicle_plate_number }} — {{ $trip->driver_name }}
                                    </a>
                                    <p class="text-sm text-slate-500">
                                        {{ $trip->originFacility->facility_name ?? '' }} → {{ $trip->destinationFacility->facility_name ?? '' }}
                                        · {{ $trip->departure_date->format('d M Y') }} · {{ ucfirst(str_replace('_', ' ', $trip->status)) }}
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        {{ __('Certificate') }}: {{ $trip->certificate->certificate_number ?: '#' . $trip->certificate_id }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('transport-trips.show', $trip) }}" class="text-sm text-bucha-primary hover:text-indigo-900">{{ __('View') }}</a>
                                    <a href="{{ route('transport-trips.edit', $trip) }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $trips->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
