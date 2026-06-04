@props(['name', 'label', 'value' => null, 'hint' => null])

@php
    $yn = old($name);
    if ($yn === null && $value !== null) {
        $yn = $value === true || $value === 1 || $value === '1' ? '1' : ($value === false || $value === 0 || $value === '0' ? '0' : '');
    }
    $yn = (string) ($yn ?? '');
@endphp

<x-wizard-field :for="$name" :label="$label" :hint="$hint">
    <select id="{{ $name }}" name="{{ $name }}" class="bucha-wizard-select" data-wizard-track>
        <option value="">{{ __('Select') }}</option>
        <option value="1" @selected($yn === '1')>{{ __('Yes') }}</option>
        <option value="0" @selected($yn === '0')>{{ __('No') }}</option>
    </select>
    <x-input-error class="mt-2" :messages="$errors->get($name)" />
</x-wizard-field>
