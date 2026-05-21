@props([
    'for' => null,
    'label' => null,
    'hint' => null,
    'required' => false,
])

<div {{ $attributes->merge(['class' => 'bucha-wizard-field']) }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="bucha-wizard-label">
            {{ $label }}
            @if ($required)
                <span class="text-bucha-primary" aria-hidden="true">*</span>
            @endif
        </label>
    @endif
    @if ($hint)
        <p class="bucha-wizard-hint">{{ $hint }}</p>
    @endif
    <div class="bucha-wizard-control">
        {{ $slot }}
    </div>
</div>
