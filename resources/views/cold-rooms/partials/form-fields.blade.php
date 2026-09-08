@props([
    'room' => null,
    'facilities' => collect(),
    'standards' => collect(),
    'submitLabel',
    'cancelRoute' => 'cold-rooms.hub',
])

@php
    $isEdit = $room !== null;
@endphp

<form method="post" action="{{ $isEdit ? route('cold-rooms.manage.update', $room) : route('cold-rooms.manage.store') }}" class="bucha-wizard-form">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <x-wizard-section :title="__('Room')">
        <x-wizard-field for="facility_id" :label="__('Storage facility')" required>
            <select id="facility_id" name="facility_id" class="bucha-wizard-select" required>
                @foreach ($facilities as $f)
                    <option value="{{ $f->id }}" @selected(old('facility_id', $room?->facility_id) == $f->id)>{{ $f->facility_name }}</option>
                @endforeach
            </select>
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />

        <x-wizard-field for="name" :label="__('Room name')" required>
            <input id="name" name="name" type="text" class="bucha-wizard-input" value="{{ old('name', $room?->name) }}" placeholder="{{ __('e.g. Chiller A') }}" required />
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('name')" />

        <div class="bucha-wizard-grid">
            <div>
                <x-wizard-field for="type" :label="__('Type')" required>
                    <select id="type" name="type" class="bucha-wizard-select" required>
                        <option value="chiller" @selected(old('type', $room?->type) === 'chiller')>{{ __('Chiller') }}</option>
                        <option value="freezer" @selected(old('type', $room?->type) === 'freezer')>{{ __('Freezer') }}</option>
                    </select>
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('type')" />
            </div>
            <div>
                <x-wizard-field for="capacity" :label="__('Capacity (optional)')">
                    <input id="capacity" name="capacity" type="number" step="0.01" min="0" class="bucha-wizard-input" value="{{ old('capacity', $room?->capacity) }}" />
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('capacity')" />
            </div>
        </div>

        <x-wizard-field for="standard_id" :label="__('Temperature standard')">
            <select id="standard_id" name="standard_id" class="bucha-wizard-select">
                <option value="">{{ $isEdit ? __('None') : __('None (monitoring disabled until set)') }}</option>
                @foreach ($standards as $s)
                    <option value="{{ $s->id }}" @selected(old('standard_id', $room?->standard_id) == $s->id)>{{ $s->name }} — {{ $s->min_temperature }}–{{ $s->max_temperature }} °C</option>
                @endforeach
            </select>
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('standard_id')" />
    </x-wizard-section>

    <div class="flex flex-wrap items-center gap-3 pt-2">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route($cancelRoute) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 rounded-bucha font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">{{ __('Cancel') }}</a>
    </div>
</form>
