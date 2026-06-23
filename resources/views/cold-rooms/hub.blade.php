@php
    use App\Models\ColdRoom;
    use App\Models\WarehouseStorage;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Cold Room') }}
            </h2>
            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="{{ route('cold-rooms.manage.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">
                    {{ __('Add cold room') }}
                </a>
                <a href="{{ route('warehouse-storages.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Record storage') }}
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

                <form method="get" action="{{ route('cold-rooms.hub') }}" class="hub-period-filter">
                    <div class="hub-period-filter__bar">
                        <div class="hub-period-filter__toggles" role="group" aria-label="{{ __('Storage period') }}">
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
                                <a href="{{ route('cold-rooms.hub') }}" class="hub-period-filter__clear">{{ __('Clear') }}</a>
                            @endif
                        </div>
                    </div>
                    <p class="hub-period-filter__hint">{{ $filters['range_label'] }}</p>
                </form>

                <div class="profile-kpi-grid profile-kpi-grid--4">
                    <x-entity.kpi-stat :label="__('Total rooms')" :value="number_format($hubStats['total_rooms'])" accent>
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="$hubStats['released_label']" :value="number_format($hubStats['released_count'])">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="$hubStats['storages_label']" :value="number_format($hubStats['storage_count'])">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('Standards')" :value="number_format($hubStats['standards'])">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                </div>

                @if ($openViolations->isNotEmpty())
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-semibold">{{ __('Open temperature violations (:count)', ['count' => $openViolations->count()]) }}</p>
                        <ul class="mt-2 space-y-1 text-xs text-red-700">
                            @foreach ($openViolations as $violation)
                                <li>
                                    {{ $violation->coldRoom->name }} — {{ $violation->coldRoom->facility->facility_name ?? '—' }}
                                    · {{ __('Started :time', ['time' => $violation->start_time->format('d M Y H:i')]) }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="space-y-3">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Cold rooms') }}</h3>
                    @if ($coldRooms->isEmpty())
                        <div class="profile-empty">
                            <p class="mb-4">{{ __('No cold rooms registered yet.') }}</p>
                            <a href="{{ route('cold-rooms.manage.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                                {{ __('Add first cold room') }}
                            </a>
                        </div>
                    @else
                        <div class="profile-cards-grid">
                            @foreach ($coldRooms as $room)
                                @php
                                    $hasViolation = $room->violations->isNotEmpty();
                                    $occupancy = $room->warehouseStorages->count();
                                    $typeTone = $room->type === ColdRoom::TYPE_CHILLER ? 'active' : 'muted';
                                    $complianceTone = $hasViolation ? 'danger' : 'active';
                                    $initial = strtoupper(substr($room->name, 0, 1));
                                @endphp
                                <x-entity.profile-card>
                                    <x-slot:avatar>{{ $initial }}</x-slot:avatar>
                                    <x-slot:title>{{ $room->name }}</x-slot:title>
                                    <x-slot:subtitle>{{ $room->facility->facility_name ?? '—' }}</x-slot:subtitle>
                                    <x-slot:badge>
                                        <x-entity.status-pill :tone="$hasViolation ? 'danger' : $typeTone" :label="ucfirst($room->type)" />
                                    </x-slot:badge>

                                    <x-entity.profile-row :label="__('Standard')">
                                        @if ($room->standard)
                                            {{ $room->standard->name }}
                                            ({{ number_format($room->standard->min_temperature, 1) }}–{{ number_format($room->standard->max_temperature, 1) }}°C)
                                        @else
                                            —
                                        @endif
                                    </x-entity.profile-row>
                                    <x-entity.profile-row :label="__('Capacity')">{{ $room->capacity ? number_format($room->capacity, 2) : '—' }}</x-entity.profile-row>
                                    <x-entity.profile-row :label="__('In storage')">{{ number_format($occupancy) }}</x-entity.profile-row>
                                    <x-entity.profile-row :label="__('Compliance')">
                                        <x-entity.status-pill
                                            :tone="$complianceTone"
                                            :label="$hasViolation ? __('Violation open') : __('In range')"
                                        />
                                    </x-entity.profile-row>

                                    <x-slot:highlights>
                                        <x-entity.profile-highlight :value="number_format($occupancy)" :label="__('Batches')" />
                                        <x-entity.profile-highlight
                                            :value="$room->standard ? number_format($room->standard->min_temperature, 1).'–'.number_format($room->standard->max_temperature, 1).'°C' : '—'"
                                            :label="__('Range')"
                                        />
                                    </x-slot:highlights>

                                    <x-slot:actions>
                                        <x-entity.text-action :href="route('cold-rooms.manage.edit', $room)">{{ __('Edit') }}</x-entity.text-action>
                                        <x-entity.text-action :href="route('warehouse-storages.index', ['cold_room_id' => $room->id])">{{ __('View storages') }}</x-entity.text-action>
                                        <x-entity.text-action-delete
                                            :action="route('cold-rooms.manage.destroy', $room)"
                                            :confirm="__('Delete this cold room?')"
                                        >{{ __('Delete') }}</x-entity.text-action-delete>
                                    </x-slot:actions>
                                </x-entity.profile-card>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="space-y-3">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Storage records') }}</h3>
                    @if ($storageRecords->isEmpty())
                        <div class="profile-empty">
                            <p class="mb-4">
                                {{ $filters['is_filtered'] ? __('No storage records in this period.') : __('No storage records yet.') }}
                            </p>
                            <a href="{{ route('warehouse-storages.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                                {{ __('Record first storage') }}
                            </a>
                        </div>
                    @else
                        <div class="profile-cards-grid">
                            @foreach ($storageRecords as $storage)
                                @php
                                    $animal = $storage->resolvedIntakeItem();
                                    $statusTone = match ($storage->status) {
                                        WarehouseStorage::STATUS_IN_STORAGE => 'active',
                                        WarehouseStorage::STATUS_RELEASED => 'muted',
                                        default => 'warning',
                                    };
                                    $cardTitle = $animal?->ear_tag ?: ($storage->batch?->batch_code ?? __('Storage #:id', ['id' => $storage->id]));
                                    $initial = strtoupper(substr($cardTitle, 0, 1));
                                @endphp
                                <x-entity.profile-card>
                                    <x-slot:avatar>{{ $initial }}</x-slot:avatar>
                                    <x-slot:title>
                                        <a href="{{ route('warehouse-storages.show', $storage) }}">{{ $cardTitle }}</a>
                                    </x-slot:title>
                                    <x-slot:subtitle>{{ $storage->coldRoom?->name ?? $storage->warehouseFacility?->facility_name ?? '—' }}</x-slot:subtitle>
                                    <x-slot:badge>
                                        <x-entity.status-pill :tone="$statusTone" :label="ucfirst(str_replace('_', ' ', $storage->status))" />
                                    </x-slot:badge>

                                    <x-entity.profile-row :label="__('Species')">
                                        {{ $animal ? $animal->species.($animal->sex ? ' · '.ucfirst($animal->sex) : '') : '—' }}
                                    </x-entity.profile-row>
                                    <x-entity.profile-row :label="__('Batch')">{{ $storage->batch?->batch_code ?? '—' }}</x-entity.profile-row>
                                    <x-entity.profile-row :label="__('Entry date')">{{ $storage->entry_date->format('d M Y') }}</x-entity.profile-row>
                                    <x-entity.profile-row :label="__('Meat stored')">
                                        {{ number_format((float) $storage->quantity_stored, 2) }} {{ $storage->quantity_unit_label }}
                                    </x-entity.profile-row>
                                    <x-entity.profile-row :label="__('Facility')">{{ $storage->warehouseFacility?->facility_name ?? '—' }}</x-entity.profile-row>
                                    @if ($storage->temperature_at_entry !== null)
                                        <x-entity.profile-row :label="__('Entry temp')">{{ number_format((float) $storage->temperature_at_entry, 1) }}°C</x-entity.profile-row>
                                    @endif

                                    <x-slot:highlights>
                                        <x-entity.profile-highlight
                                            :value="number_format((float) $storage->quantity_stored, 2)"
                                            :label="$storage->quantity_unit_label"
                                        />
                                        <x-entity.profile-highlight :value="$storage->entry_date->format('d M Y')" :label="__('Entered')" />
                                    </x-slot:highlights>

                                    <x-slot:actions>
                                        <x-entity.text-action :href="route('warehouse-storages.show', $storage)">{{ __('View') }}</x-entity.text-action>
                                        <x-entity.text-action :href="route('warehouse-storages.edit', $storage)">{{ __('Edit') }}</x-entity.text-action>
                                        <x-entity.text-action-delete
                                            :action="route('warehouse-storages.destroy', $storage)"
                                            :confirm="__('Delete this storage record? The animal will be available to store again.')"
                                        >{{ __('Delete') }}</x-entity.text-action-delete>
                                    </x-slot:actions>
                                </x-entity.profile-card>
                            @endforeach
                        </div>
                        <div class="mt-4">{{ $storageRecords->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
