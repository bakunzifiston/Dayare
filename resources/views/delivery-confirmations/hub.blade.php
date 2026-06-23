@php
    use App\Models\DeliveryConfirmation;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Delivery confirmations') }}
            </h2>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @include('processor.partials.export-dropdown', [
                    'exportRoute' => 'delivery-confirmations.export',
                    'query' => $exportQuery ?? [],
                ])
                <a href="{{ route('delivery-confirmations.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Confirm delivery') }}
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

                <form method="get" action="{{ route('delivery-confirmations.hub') }}" class="hub-period-filter">
                    <div class="hub-period-filter__bar">
                        <div class="hub-period-filter__toggles" role="group" aria-label="{{ __('Received period') }}">
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
                                <a href="{{ route('delivery-confirmations.hub') }}" class="hub-period-filter__clear">{{ __('Clear') }}</a>
                            @endif
                        </div>
                    </div>
                    <p class="hub-period-filter__hint">{{ $filters['range_label'] }}</p>
                </form>

                <div class="profile-kpi-grid">
                    <x-entity.kpi-stat :label="$hubStats['confirmations_label']" :value="number_format($hubStats['total_confirmations'])" accent>
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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
                    <x-entity.kpi-stat :label="__('Confirmed')" :value="number_format($hubStats['confirmed'])" :accent="$hubStats['confirmed'] > 0">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat
                        :label="__('Disputed')"
                        :value="number_format($hubStats['disputed'])"
                        :accent="$hubStats['disputed'] > 0"
                    >
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat
                        :label="__('Awaiting confirmation')"
                        :value="number_format($hubStats['awaiting_confirmation'])"
                        :accent="$hubStats['awaiting_confirmation'] > 0"
                    >
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('International')" :value="number_format($hubStats['international'])">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                </div>

                @if ($pendingTrips->isNotEmpty())
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                        <p class="text-sm font-medium text-amber-900">
                            {{ __(':count trip(s) awaiting delivery confirmation', ['count' => $hubStats['awaiting_confirmation']]) }}
                        </p>
                        <ul class="mt-2 space-y-1.5">
                            @foreach ($pendingTrips as $trip)
                                <li class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                    <span class="font-mono text-amber-950">{{ $trip->vehicle_plate_number }}</span>
                                    <a href="{{ route('delivery-confirmations.create', ['transport_trip_id' => $trip->id]) }}" class="text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy">
                                        {{ __('Confirm →') }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($confirmations->isEmpty())
                    <div class="profile-empty">
                        <p class="mb-4">
                            {{ $filters['is_filtered'] ? __('No delivery confirmations in this period.') : __('No delivery confirmations yet.') }}
                        </p>
                        <a href="{{ route('delivery-confirmations.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Confirm first delivery') }}
                        </a>
                    </div>
                @else
                    <div class="profile-cards-grid">
                        @foreach ($confirmations as $confirmation)
                            @php
                                $trip = $confirmation->transportTrip;
                                $statusTone = match ($confirmation->confirmation_status) {
                                    DeliveryConfirmation::STATUS_CONFIRMED => 'active',
                                    DeliveryConfirmation::STATUS_DISPUTED => 'danger',
                                    default => 'warning',
                                };
                                $statusLabel = ucfirst($confirmation->confirmation_status);
                                $initial = strtoupper(substr($trip?->vehicle_plate_number ?? 'D', 0, 1));
                                $quantityLabel = number_format($confirmation->received_quantity).' '.($confirmation->received_unit ?? __('units'));
                            @endphp
                            <x-entity.profile-card>
                                <x-slot:avatar>{{ $initial }}</x-slot:avatar>
                                <x-slot:title>
                                    <a href="{{ route('delivery-confirmations.show', $confirmation) }}">{{ $confirmation->receiver_display }}</a>
                                </x-slot:title>
                                <x-slot:subtitle>
                                    {{ $trip?->vehicle_plate_number ?? '—' }}
                                    @if ($trip)
                                        · {{ $trip->originFacility?->facility_name ?? '—' }} → {{ $trip->destination_display }}
                                    @endif
                                </x-slot:subtitle>
                                <x-slot:badge>
                                    <x-entity.status-pill :tone="$statusTone" :label="$statusLabel" />
                                </x-slot:badge>

                                <x-entity.profile-row :label="__('Received')">{{ $confirmation->received_date->format('d M Y') }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Quantity')">{{ $quantityLabel }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Receiver')">{{ $confirmation->receiver_name }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Client')">{{ $confirmation->client?->display_name ?? '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Trip')">
                                    @if ($trip)
                                        <a href="{{ route('transport-trips.show', $trip) }}" class="text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy">
                                            {{ $trip->vehicle_plate_number }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </x-entity.profile-row>
                                <x-entity.profile-row :label="__('Export')">
                                    @if ($confirmation->isInternationalExport())
                                        {{ $confirmation->allExportDocumentsIssued() ? __('Docs complete') : __('Docs pending') }}
                                    @else
                                        —
                                    @endif
                                </x-entity.profile-row>

                                <x-slot:highlights>
                                    <x-entity.profile-highlight
                                        :value="$confirmation->received_date->format('d M Y')"
                                        :label="__('Received')"
                                    />
                                    <x-entity.profile-highlight
                                        :value="number_format($confirmation->received_quantity)"
                                        :label="$confirmation->received_unit ?? __('Units')"
                                    />
                                </x-slot:highlights>

                                <x-slot:actions>
                                    <x-entity.text-action :href="route('delivery-confirmations.show', $confirmation)">{{ __('View') }}</x-entity.text-action>
                                    <x-entity.text-action :href="route('delivery-confirmations.edit', $confirmation)">{{ __('Edit') }}</x-entity.text-action>
                                    @if ($confirmation->isInternationalExport())
                                        <x-entity.text-action :href="route('export-documents.index', $confirmation)">{{ __('Export docs') }}</x-entity.text-action>
                                    @endif
                                    <x-entity.text-action-delete
                                        :action="route('delivery-confirmations.destroy', $confirmation)"
                                        :confirm="__('Are you sure you want to delete this delivery confirmation?')"
                                    >{{ __('Delete') }}</x-entity.text-action-delete>
                                </x-slot:actions>
                            </x-entity.profile-card>
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $confirmations->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
