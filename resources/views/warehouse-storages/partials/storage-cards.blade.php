@props([
    'storages',
    'showPagination' => false,
])

@php
    use App\Models\WarehouseStorage;
@endphp

<div class="profile-cards-grid">
    @foreach ($storages as $storage)
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
            @if ($storage->certificate)
                <x-entity.profile-row :label="__('Certificate')">
                    {{ $storage->certificate->certificate_number ?: '#'.$storage->certificate_id }}
                </x-entity.profile-row>
            @endif
            <x-entity.profile-row :label="__('Entry date')">{{ $storage->entry_date->format('d M Y') }}</x-entity.profile-row>
            <x-entity.profile-row :label="__('Meat stored')">
                {{ number_format((float) $storage->quantity_stored, 2) }} {{ $storage->quantity_unit_label }}
            </x-entity.profile-row>
            <x-entity.profile-row :label="__('Facility')">{{ $storage->warehouseFacility?->facility_name ?? '—' }}</x-entity.profile-row>
            @if ($animal?->live_weight_kg)
                <x-entity.profile-row :label="__('Live weight')">
                    {{ number_format((float) $animal->live_weight_kg, 2) }} kg
                </x-entity.profile-row>
            @endif
            @if ($storage->postMortemInspectionItem?->carcass_weight_kg)
                <x-entity.profile-row :label="__('Carcass')">
                    {{ number_format((float) $storage->postMortemInspectionItem->carcass_weight_kg, 2) }} kg
                </x-entity.profile-row>
            @endif
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

@if ($showPagination && method_exists($storages, 'links'))
    <div class="mt-4">{{ $storages->links() }}</div>
@endif
