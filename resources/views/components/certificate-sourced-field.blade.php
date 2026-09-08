@props([
    'name',
    'label',
    'value' => '',
    'locked' => false,
    'type' => 'text',
    'required' => false,
    'hint' => null,
    'placeholder' => null,
    'mono' => false,
    'emptyDisplay' => '—',
])

@php
    $display = filled($value) ? $value : $emptyDisplay;
@endphp

<x-wizard-field :for="$name" :label="$label" :hint="$locked ? null : $hint" :required="$required && ! $locked">
    @if ($locked)
        <p @class([
            'bucha-wizard-input flex items-center bg-slate-50 text-slate-800 border-slate-200',
            'font-mono tracking-wide' => $mono,
        ])>{{ $display }}</p>
        <input
            type="hidden"
            id="{{ $name }}"
            name="{{ $name }}"
            value="{{ $value }}"
            data-certificate-sourced="1"
        />
    @else
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            class="bucha-wizard-input{{ $mono ? ' font-mono tracking-wide' : '' }}"
            value="{{ $value }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($required) required @endif
            {{ $attributes }}
        />
    @endif
</x-wizard-field>
