@props([
    'label',
    'value',
    'accent' => false,
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'profile-kpi']) }}>
    <div class="profile-kpi__head">
        <p class="profile-kpi__label">{{ $label }}</p>
        @if (isset($icon))
            <div class="profile-kpi__icon" aria-hidden="true">{{ $icon }}</div>
        @endif
    </div>
    <p @class(['profile-kpi__value', 'profile-kpi__value--accent' => $accent])>{{ $value }}</p>
    @if ($hint)
        <p class="profile-kpi__hint">{{ $hint }}</p>
    @endif
</div>
