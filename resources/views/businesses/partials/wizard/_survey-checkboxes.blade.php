@props([
    'name',
    'options' => [],
    'selected' => [],
    'otherName' => null,
    'otherValue' => '',
    'otherShowKey' => null,
])

@php
    $selected = (array) $selected;
@endphp

<div class="bucha-wizard-checkbox-grid">
    @foreach ($options as $value => $label)
        <label class="bucha-wizard-checkbox">
            <input
                type="checkbox"
                name="{{ $name }}[]"
                value="{{ $value }}"
                @checked(in_array($value, $selected, true))
                @if($otherShowKey && $value === $otherShowKey) @change="$dispatch('survey-other-toggle')" @endif
                data-wizard-track
            />
            <span>{{ $label }}</span>
        </label>
    @endforeach
</div>
@if ($otherName)
    <div class="mt-3" @if($otherShowKey && ! in_array($otherShowKey, $selected, true)) hidden @endif>
        <x-wizard-field :for="$otherName" :label="__('Other (please specify)')">
            <input id="{{ $otherName }}" name="{{ $otherName }}" type="text" class="bucha-wizard-input" value="{{ $otherValue }}" data-wizard-track />
        </x-wizard-field>
    </div>
@endif
