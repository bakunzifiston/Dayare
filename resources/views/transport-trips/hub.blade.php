@php
    use App\Models\TransportTrip;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Transport') }}
            </h2>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @include('processor.partials.export-dropdown', [
                    'exportRoute' => 'transport-trips.export',
                    'traceabilityRoute' => 'transport-trips.export.traceability',
                    'query' => $exportQuery ?? [],
                ])
                <a href="{{ route('transport-trips.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Record trip') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="profile-list-shell">
                @if (session('status'))
                    <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
                @endif

                <form method="get" action="{{ route('transport-trips.hub') }}" class="hub-period-filter">
                    <div class="hub-period-filter__bar">
                        <div class="hub-period-filter__toggles" role="group" aria-label="{{ __('Departure period') }}">
                            @foreach (['all' => __('All'), 'day' => __('Daily'), 'month' => __('Monthly'), 'year' => __('Yearly')] as $periodKey => $periodLabel)
                                <label class="hub-period-filter__toggle">
                                    <input type="radio" name="period" value="{{ $periodKey }}" @checked($filters['period'] === $periodKey)>
                                    <span>{{ $periodLabel }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="hub-period-filter__range">
                            <label for="filter_date_from" class="hub-period-filter__range-label">{{ __('From') }}</label>
                            <input id="filter_date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="hub-period-filter__input" aria-label="{{ __('Date from') }}">
                            <span class="hub-period-filter__sep" aria-hidden="true">–</span>
                            <label for="filter_date_to" class="hub-period-filter__range-label">{{ __('To') }}</label>
                            <input id="filter_date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="hub-period-filter__input" aria-label="{{ __('Date to') }}">
                        </div>

                        <div class="hub-period-filter__actions">
                            <button type="submit" class="hub-period-filter__apply">{{ __('Apply') }}</button>
                            @if ($filters['is_filtered'])
                                <a href="{{ route('transport-trips.hub') }}" class="hub-period-filter__clear">{{ __('Clear') }}</a>
                            @endif
                        </div>
                    </div>
                    <p class="hub-period-filter__hint">{{ $filters['range_label'] }}</p>
                </form>

                <div class="profile-kpi-grid">
                    <x-entity.kpi-stat :label="$hubStats['trips_label']" :value="number_format($hubStats['total_trips'])" accent>
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat
                        :label="__('Pending')"
                        :value="number_format($hubStats['pending'])"
                        :accent="$hubStats['pending'] > 0"
                    >
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat
                        :label="__('In transit')"
                        :value="number_format($hubStats['in_transit'])"
                        :accent="$hubStats['in_transit'] > 0"
                    >
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat
                        :label="__('Arrived')"
                        :value="number_format($hubStats['arrived'])"
                        :accent="$hubStats['arrived'] > 0"
                    >
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('With delivery confirmation')" :value="number_format($hubStats['with_delivery_confirmation'])">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                </div>

                @if ($trips->isEmpty())
                    <div class="profile-empty">
                        <p class="mb-4">
                            {{ $filters['is_filtered'] ? __('No transport trips in this period.') : __('No transport trips recorded yet.') }}
                        </p>
                        <a href="{{ route('transport-trips.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Record first trip') }}
                        </a>
                    </div>
                @else
                    <div class="profile-cards-grid">
                        @foreach ($trips as $trip)
                            @php
                                $statusTone = match ($trip->status) {
                                    TransportTrip::STATUS_PENDING => 'warning',
                                    TransportTrip::STATUS_IN_TRANSIT => 'active',
                                    TransportTrip::STATUS_ARRIVED => 'warning',
                                    TransportTrip::STATUS_COMPLETED => 'muted',
                                    default => 'muted',
                                };
                                $statusLabel = ucfirst(str_replace('_', ' ', $trip->status));
                                $initial = strtoupper(substr($trip->vehicle_plate_number, 0, 1));
                                $certLabel = $trip->certificate?->certificate_number ?: '#'.$trip->certificate_id;
                                $routeLabel = ($trip->originFacility?->facility_name ?? '—').' → '.$trip->destination_display;
                            @endphp
                            <x-entity.profile-card>
                                <x-slot:avatar>{{ $initial }}</x-slot:avatar>
                                <x-slot:title>
                                    <a href="{{ route('transport-trips.show', $trip) }}">{{ $trip->vehicle_plate_number }}</a>
                                </x-slot:title>
                                <x-slot:subtitle>{{ $routeLabel }}</x-slot:subtitle>
                                <x-slot:badge>
                                    <x-entity.status-pill :tone="$statusTone" :label="$statusLabel" />
                                </x-slot:badge>

                                <x-entity.profile-row :label="__('Driver')">{{ $trip->driver_name }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Certificate')">
                                    @if ($trip->certificate)
                                        <a href="{{ route('certificates.show', $trip->certificate) }}" class="text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy">
                                            {{ $certLabel }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </x-entity.profile-row>
                                <x-entity.profile-row :label="__('Departure')">{{ $trip->departure_date->format('d M Y') }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Arrival')">
                                    {{ $trip->arrival_date?->format('d M Y') ?? '—' }}
                                </x-entity.profile-row>
                                <x-entity.profile-row :label="__('Delivery')">
                                    {{ $trip->deliveryConfirmation ? __('Confirmed') : __('Pending') }}
                                </x-entity.profile-row>

                                <x-slot:highlights>
                                    <x-entity.profile-highlight
                                        :value="$trip->departure_date->format('d M Y')"
                                        :label="__('Departure')"
                                    />
                                    <x-entity.profile-highlight
                                        :value="$trip->destination_display"
                                        :label="__('Destination')"
                                    />
                                </x-slot:highlights>

                                <x-slot:actions>
                                    <x-entity.text-action :href="route('transport-trips.show', $trip)">{{ __('View') }}</x-entity.text-action>
                                    <x-entity.text-action :href="route('transport-trips.edit', $trip)">{{ __('Edit') }}</x-entity.text-action>
                                    @if (! $trip->deliveryConfirmation)
                                        <x-entity.text-action :href="route('delivery-confirmations.create', ['transport_trip_id' => $trip->id])">{{ __('Confirm delivery') }}</x-entity.text-action>
                                        <x-entity.text-action-delete
                                            :action="route('transport-trips.destroy', $trip)"
                                            :confirm="__('Are you sure you want to delete this transport trip?')"
                                        >{{ __('Delete') }}</x-entity.text-action-delete>
                                    @else
                                        <x-entity.text-action :href="route('delivery-confirmations.show', $trip->deliveryConfirmation)">{{ __('Delivery') }}</x-entity.text-action>
                                    @endif
                                </x-slot:actions>
                            </x-entity.profile-card>
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $trips->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
