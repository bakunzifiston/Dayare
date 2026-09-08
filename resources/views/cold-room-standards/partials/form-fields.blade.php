@props([
    'standard' => null,
    'submitLabel',
])

@php
    $isEdit = $standard !== null;
@endphp

<form method="post" action="{{ $isEdit ? route('cold-room-standards.update', $standard) : route('cold-room-standards.store') }}" class="bucha-wizard-form">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <x-wizard-section :title="__('Standard')">
        <x-wizard-field for="name" :label="__('Name')" required>
            <input id="name" name="name" type="text" class="bucha-wizard-input" value="{{ old('name', $standard?->name) }}" required />
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('name')" />

        <x-wizard-field for="type" :label="__('Type')" required>
            <select id="type" name="type" class="bucha-wizard-select" required>
                <option value="chiller" @selected(old('type', $standard?->type) === 'chiller')>{{ __('Chiller') }}</option>
                <option value="freezer" @selected(old('type', $standard?->type) === 'freezer')>{{ __('Freezer') }}</option>
            </select>
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('type')" />

        <div class="bucha-wizard-grid">
            <div>
                <x-wizard-field for="min_temperature" :label="__('Min temperature (°C)')" required>
                    <input id="min_temperature" name="min_temperature" type="number" step="0.01" class="bucha-wizard-input" value="{{ old('min_temperature', $standard?->min_temperature) }}" required />
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('min_temperature')" />
            </div>
            <div>
                <x-wizard-field for="max_temperature" :label="__('Max temperature (°C)')" required>
                    <input id="max_temperature" name="max_temperature" type="number" step="0.01" class="bucha-wizard-input" value="{{ old('max_temperature', $standard?->max_temperature) }}" required />
                </x-wizard-field>
                <x-input-error class="mt-2" :messages="$errors->get('max_temperature')" />
            </div>
        </div>

        <x-wizard-field for="tolerance_minutes" :label="__('Tolerance (minutes)')" required>
            <input id="tolerance_minutes" name="tolerance_minutes" type="number" min="0" class="bucha-wizard-input" value="{{ old('tolerance_minutes', $standard?->tolerance_minutes ?? 30) }}" required />
        </x-wizard-field>
        <x-input-error class="mt-2" :messages="$errors->get('tolerance_minutes')" />
    </x-wizard-section>

    <div class="flex flex-wrap items-center gap-3 pt-2">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ route('cold-room-standards.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 rounded-bucha font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">{{ __('Cancel') }}</a>
    </div>
</form>
